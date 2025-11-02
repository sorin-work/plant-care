<?php

namespace App\Enum;

enum Hemisphere: string
{
    case North = 'north';
    case South = 'south';

    public function label(): string
    {
        return match ($this) {
            self::North => 'Northern hemisphere',
            self::South => 'Southern hemisphere',
        };
    }
}
