<?php

namespace App\Message\Video;

use App\Entity\Video;
use App\Service\VideoService;
use App\ValueObject\FileSize;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use RuntimeException;
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
        $video = $this->entityManager->getRepository(Video::class)->findOneBy([
                'uuid'     => $message->videoId,
                'playlist' => $message->playListId
            ])
        ;

        if (!$video) {
            throw new RuntimeException('Video not found');
        }

        $workflow = $this->workflowRegistry->get($video, 'video_processing');

        if ($workflow->can($video, 'start_processing')) {
            $workflow->apply($video, 'start_processing');
        } else {
            throw new LogicException('Video cannot be processed.');
        }
        // Сохранение сущности Video после изменения состояния
        $this->entityManager->flush();

        try {
            $videoOutput = $this->videoService->processVideo($message);

            $video->setSize($videoOutput->fileSize->convertTo(FileSize::UNIT_KILOBYTE))
                  ->setImagePreview($videoOutput->posterPath)
                  ->setPath($videoOutput->videoPath)
            ;

            $workflow->can($video, 'complete')
                ? $workflow->apply($video, 'complete')
                : throw new LogicException('Video cannot be completed.');

            # Загруженное видео удаляется только если processVideo произошел успешно
            $directoryPath = dirname($message->videoInputPath);
            $this->filesystem->remove($directoryPath);
        } catch (\Exception $e) {
            // Применяем переход 'fail' в случае ошибки
            if ($workflow->can($video, 'fail')) {
                $workflow->apply($video, 'fail');
            }
            throw $e;
        }

        $this->entityManager->persist($video);
        $this->entityManager->flush();
    }
}