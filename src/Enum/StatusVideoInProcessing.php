<?php

namespace App\Enum;

enum StatusVideoInProcessing: string
{
    case inQueue = 'in queue';
    case processing = 'processing';
    case done = 'done';


    public function getLable(): string {
        return match($this) {
            self::inQueue    => strtoupper(self::inQueue->value),
            self::processing => strtoupper(self::processing->value),
            self::done       => strtoupper(self::done->value),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::inQueue    => 'gray',
            self::processing => 'orange',
            self::done       => 'success',
        };
	}

    public function getIcon(): ?string
    {
        return match ($this) {
            self::inQueue    => 'fas fa-envelope',
            self::processing => 'fas fa-hourglass-half',
            self::done       => 'fas fa-check',
        };
	}
}



//            fas fa-envelope
//            fas fa-hourglass-half
//            fas fa-check
//            MenuItem::linkToCrud('Процессинг видео', 'fas fa-hourglass-half', Video::class),