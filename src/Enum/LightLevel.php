<?php

namespace App\Enum;

enum LightLevel: string
{
    case Low = 'low';
    case Medium = 'medium';
    case BrightIndirect = 'bright_indirect';
    case Direct = 'direct';

    public function label(): string
    {
        return match ($this) {
            self::Low => 'Low light',
            self::Medium => 'Medium light',
            self::BrightIndirect => 'Bright, indirect',
            self::Direct => 'Direct sun',
        };
    }
}
