<?php

namespace App\Enum;

enum CareTaskType: string
{
    case Water = 'water';
    case Fertilize = 'fertilize';
    case Repot = 'repot';

    public function label(): string
    {
        return match ($this) {
            self::Water => 'Water',
            self::Fertilize => 'Fertilize',
            self::Repot => 'Repot',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Water => '💧',
            self::Fertilize => '🌱',
            self::Repot => '🪴',
        };
    }
}
