<?php

namespace App\Enum;

enum Season: string
{
    case Winter = 'winter';
    case Spring = 'spring';
    case Summer = 'summer';
    case Autumn = 'autumn';

    public function opposite(): self
    {
        return match ($this) {
            self::Winter => self::Summer,
            self::Spring => self::Autumn,
            self::Summer => self::Winter,
            self::Autumn => self::Spring,
        };
    }

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
