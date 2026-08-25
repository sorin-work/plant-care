<?php

namespace App\Enum;

enum ArchiveReason: string
{
    case GaveAway = 'gave_away';
    case Died = 'died';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::GaveAway => 'Gave it away',
            self::Died => 'It did not make it',
            self::Other => 'Other reason',
        };
    }
}
