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
    /**
     * How long since a date, in the largest sensible unit: "3 weeks",
     * "5 months", "1 year and 2 months".
     */
    public static function duration(DateTimeInterface $since, DateTimeInterface $today): string
    {
        $diff = DateTimeImmutable::createFromInterface($since)->setTime(0, 0)->diff(DateTimeImmutable::createFromInterface($today)->setTime(0, 0));
        $plural = static fn (int $n, string $unit): string => \sprintf('%d %s%s', $n, $unit, 1 === $n ? '' : 's');

        if ($diff->invert || 0 === (int) $diff->days) {
            return 'less than a day';
        }
        if ($diff->y > 0) {
            return $plural($diff->y, 'year').($diff->m > 0 ? ' and '.$plural($diff->m, 'month') : '');
        }
        if ($diff->m > 0) {
            return $plural($diff->m, 'month');
        }
        if ($diff->d >= 7) {
            return $plural(intdiv($diff->d, 7), 'week');
        }

        return $plural($diff->d, 'day');
    }

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
