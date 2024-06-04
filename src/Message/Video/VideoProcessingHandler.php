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
        file_put_contents('r.txt', serialize($message));

        $input = $message->videoInputPath;
        $output = $message->videoOutputPath;

        $this->videoService->proccesVideo($input, $output);
    }
}