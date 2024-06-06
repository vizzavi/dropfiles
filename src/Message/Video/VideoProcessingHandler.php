<?php

namespace App\Message\Video;

use App\Service\VideoService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class VideoProcessingHandler
{
    public function __construct(private VideoService $videoService)
    {
    }

    public function __invoke(VideoProcessingMessege $message): void
    {
        $input = $message->videoInputPath;
        $output = $message->videoOutputPath;

        $this->videoService->processVideo($input, $output);
    }
}