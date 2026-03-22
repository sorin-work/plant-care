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
     * @return list<array{day: DateTimeImmutable, schedules: list<CareSchedule>}> in date order
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
            $byDay[$due->format('Y-m-d')][] = $schedule;
        }

        // Sort on the ISO date, not on a display label.
        ksort($byDay, \SORT_STRING);

        $days = [];
        foreach ($byDay as $date => $schedules) {
            $days[] = ['day' => new DateTimeImmutable($date), 'schedules' => $schedules];
        }

        return $days;
    }
}
