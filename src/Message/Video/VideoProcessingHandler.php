<?php

namespace App\Message\Video;

use App\Entity\Video;
use App\Service\VideoService;
use App\ValueObject\FileSize;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Workflow\Registry;

#[AsMessageHandler]
readonly class VideoProcessingHandler
{
    public function __construct(
        private VideoService $videoService,
        private Registry $workflowRegistry,
        private EntityManagerInterface $entityManager,
        private Filesystem $filesystem
    ) {
    }

    public function __invoke(VideoProcessingMessege $message): void
    {
        $video = $this->entityManager
            ->getRepository(Video::class)
            ->findOneBy([
                'uuid' => $message->videoId,
                'playlist' => $message->playListId
            ])
        ;

        if (!$video) {
            throw new \RuntimeException('Video not found');
        }

        $workflow = $this->workflowRegistry->get($video, 'video_processing');
        $workflow->apply($video, 'start_processing');
        // Сохранение сущности Video после изменения состояния
        $this->entityManager->flush();

        $videoOutput = $this->videoService->processVideo($message);

        $workflow->apply($video, 'complete');
        $video
            ->setSize($videoOutput->fileSize->convertTo(FileSize::UNIT_KILOBYTE))
            ->setImagePreview($videoOutput->posterPath)
            ->setPath($videoOutput->videoPath)
        ;

        $this->entityManager->persist($video);
        $this->entityManager->flush();

        $directoryPath = dirname($message->videoInputPath);
        $this->filesystem->remove($directoryPath);
    }
}