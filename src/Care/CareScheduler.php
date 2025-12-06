<?php

namespace App\Care;

use App\Entity\CareSchedule;
use DateTimeImmutable;

/**
 * Moves schedules forward when care is done.
 */
final class CareScheduler
{
    public function __construct(
        private readonly SeasonResolver $seasons,
        private readonly SeasonalIntervalCalculator $intervals,
    ) {
    }

    /**
     * The interval that applies when care is done on the given day.
     */
    public function intervalOn(CareSchedule $schedule, DateTimeImmutable $day): int
    {
        if (!$schedule->isSeasonal()) {
            return $schedule->getIntervalDays();
        }

        $season = $this->seasons->seasonOn($day, $schedule->getPlant()->getHousehold()->getHemisphere());

        return $this->intervals->effectiveInterval($schedule->getType(), $schedule->getIntervalDays(), $season);
    }

    public function nextDueAfter(CareSchedule $schedule, DateTimeImmutable $day): DateTimeImmutable
    {
        return $day->setTime(0, 0)->modify(\sprintf('+%d days', $this->intervalOn($schedule, $day)));
    }

    public function complete(CareSchedule $schedule, DateTimeImmutable $day): void
    {
        $schedule->setLastDoneOn($day->setTime(0, 0));
        $schedule->setNextDueOn($this->nextDueAfter($schedule, $day));
        $schedule->setSnoozedUntil(null);
    }
}
