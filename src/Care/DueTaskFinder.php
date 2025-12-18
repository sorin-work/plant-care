<?php

namespace App\Care;

use App\Entity\CareSchedule;
use App\Entity\Household;
use App\Repository\CareScheduleRepository;
use DateTimeImmutable;

final class DueTaskFinder
{
    public function __construct(private readonly CareScheduleRepository $schedules)
    {
    }

    /**
     * Tasks due on or before the given day, most overdue first.
     *
     * @return list<DueTask>
     */
    public function dueOn(Household $household, DateTimeImmutable $today): array
    {
        $today = $today->setTime(0, 0);

        return array_map(
            fn (CareSchedule $s): DueTask => new DueTask($s, (int) $s->getNextDueOn()->diff($today)->format('%r%a')),
            $this->schedules->findDue($household, $today),
        );
    }
}
