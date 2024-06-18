<?php

namespace App\Enum;

enum ProcessingStatus: string
{
    case queue = 'queued';
    case processing = 'processing';
    case done = 'done';

    case transitionStartProcessing = 'start_processing';
    case transitionComplete = 'complete';

    public function getLable(): string
    {
        return match ($this) {
            self::queue      => strtoupper(self::queue->value),
            self::processing => strtoupper(self::processing->value),
            self::done       => strtoupper(self::done->value),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::queue      => 'gray',
            self::processing => 'orange',
            self::done       => 'success',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::queue      => 'fas fa-envelope',
            self::processing => 'fas fa-hourglass-half',
            self::done       => 'fas fa-check',
        };
    }
}
