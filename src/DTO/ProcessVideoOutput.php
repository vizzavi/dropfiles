<?php

namespace App\DTO;

use App\ValueObject\FileSize;

readonly class ProcessVideoOutput
{
    public function __construct(
        public FileSize $fileSize,
        public string   $posterPath,
        public string   $videoPath,
    ) {
    }
}