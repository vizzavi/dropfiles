<?php

namespace App\Enum;

use DateTimeImmutable;

enum FileLifeTime: string
{
    case sixMonth = '6 months';
    case oneYear = '1 year';
    case forever = 'forever';

    public function getDate(): ?DateTimeImmutable {
        $sixMonths = new DateTimeImmutable('+6 months');
        $oneYear = new DateTimeImmutable('+1 year');

        return match($this) {
            self::sixMonth => $sixMonths,
            self::oneYear  => $oneYear,
            self::forever  => null,
        };
    }
}
