<?php

namespace App\Message\Video;

use Symfony\Component\Uid\Uuid;

readonly class VideoProcessingMessege
{
    public function __construct(
        public Uuid|string $videoId,
        public Uuid|string $playListId,
        public string $videoInputPath,
        public string $videoOutputPath,
        public string $videoName,
        public string $storageDuration,
    ) {
    }
}