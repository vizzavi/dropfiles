<?php

namespace App\ValueObject;

class FileSize
{
    const string UNIT_BYTE     = 'B';
    const string UNIT_KILOBYTE = 'KB';
    const string UNIT_MEGABYTE = 'MB';
    const string UNIT_GIGABYTE = 'GB';

    public function __construct(private $value, private $unit)
    {
    }

    private function toBytes(): float|int
    {
        return match ($this->unit) {
            self::UNIT_BYTE     => $this->value,
            self::UNIT_KILOBYTE => $this->value * 1024,
            self::UNIT_MEGABYTE => $this->value * 1024 * 1024,
            self::UNIT_GIGABYTE => $this->value * 1024 * 1024 * 1024,
            default             => throw new \InvalidArgumentException("Invalid unit provided"),
        };
    }

    public function convertTo($targetUnit): float|int
    {
        $bytes = $this->toBytes();

        return match ($targetUnit) {
            self::UNIT_BYTE     => $bytes,
            self::UNIT_KILOBYTE => $bytes / 1024,
            self::UNIT_MEGABYTE => $bytes / (1024 * 1024),
            self::UNIT_GIGABYTE => $bytes / (1024 * 1024 * 1024),
            default             => throw new \InvalidArgumentException("Invalid target unit provided"),
        };
    }
}