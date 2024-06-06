<?php

namespace App\ValueObject;

class FileSize
{
    public const string UNIT_BYTE     = 'B';
    public const string UNIT_KILOBYTE = 'KB';
    public const string UNIT_MEGABYTE = 'MB';
    public const string UNIT_GIGABYTE = 'GB';

    public function __construct(private readonly float|int $value, private readonly string $unit)
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

    public function convertTo($targetUnit): int|float
    {
        $bytes = $this->toBytes();

        return match ($targetUnit) {
            self::UNIT_BYTE     => $bytes,
            self::UNIT_KILOBYTE => $bytes / 1024,
            self::UNIT_MEGABYTE => round($bytes / (1024 * 1024), 1),
            self::UNIT_GIGABYTE => round($bytes / (1024 * 1024 * 1024), 1),
            default             => throw new \InvalidArgumentException("Invalid target unit provided"),
        };
    }

    public function getValue(): float|int
    {
        return $this->value;
    }

    public function getUnit(): string
    {
        return $this->unit;
    }
}