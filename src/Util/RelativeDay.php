<?php

namespace App\Util;

use DateTimeImmutable;
use DateTimeInterface;

/**
 * Human wording for a date relative to today: "today", "yesterday",
 * "3 days ago", "in 2 days".
 */
final class RelativeDay
{
    public static function format(DateTimeInterface $date, DateTimeInterface $today): string
    {
        $from = DateTimeImmutable::createFromInterface($today)->setTime(0, 0);
        $to = DateTimeImmutable::createFromInterface($date)->setTime(0, 0);
        $days = (int) $from->diff($to)->format('%r%a');

        return match (true) {
            0 === $days => 'today',
            -1 === $days => 'yesterday',
            1 === $days => 'tomorrow',
            $days < 0 => \sprintf('%d days ago', -$days),
            default => \sprintf('in %d days', $days),
        };
    }
}
