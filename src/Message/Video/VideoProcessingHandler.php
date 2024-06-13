<?php

namespace App\Message\Video;

use App\Entity\Video;
use App\Service\VideoService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Workflow\Registry;

#[AsMessageHandler]
readonly class VideoProcessingHandler
{
    public function __construct(
        private VideoService $videoService,
        private Registry $workflowRegistry,
        private EntityManagerInterface $entityManager
    ) {
    }

    public function __invoke(VideoProcessingMessege $message): void
    {
        $input = $message->videoInputPath;
        $output = $message->videoOutputPath;

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

        $this->videoService->processVideo($input, $output);

        # TODO: Очистка загруженного видео

        $workflow->apply($video, 'complete');
        $this->entityManager->flush();
    }
}