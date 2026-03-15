<?php

namespace App\Care;

use App\Entity\CareSchedule;
use App\Entity\Household;
use App\Repository\CareScheduleRepository;
use DateTimeImmutable;

/**
 * What is coming up in the next days, so you can plan a weekend away.
 */
final class UpcomingPlanner
{
    public function __construct(private readonly CareScheduleRepository $schedules)
    {
    }

    /**
     * @return array<string, list<CareSchedule>> day label => schedules due that day
     */
    public function nextDays(Household $household, DateTimeImmutable $today, int $days = 7): array
    {
        $today = $today->setTime(0, 0);
        $until = $today->modify(\sprintf('+%d days', $days));

        $byDay = [];
        foreach ($this->schedules->findComingUp($household, $today, $until) as $schedule) {
            $due = max($schedule->getNextDueOn(), $schedule->getSnoozedUntil() ?? $schedule->getNextDueOn());
            if ($due <= $today || $due > $until) {
                continue;
            }
            $byDay[$due->format('D j M')][] = $schedule;
        }

        ksort($byDay);

        return $byDay;
    }
}
