<?php

namespace App\Care;

use App\Entity\CareSchedule;
use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Moves schedules forward when care is done.
 */
final class CareScheduler
{
    public const MAX_SNOOZE_DAYS = 14;

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

    public function snooze(CareSchedule $schedule, int $days, DateTimeImmutable $today): void
    {
        if ($days < 1 || $days > self::MAX_SNOOZE_DAYS) {
            throw new InvalidArgumentException(\sprintf('A task can be snoozed for 1 to %d days.', self::MAX_SNOOZE_DAYS));
        }

        $schedule->setSnoozedUntil($today->setTime(0, 0)->modify(\sprintf('+%d days', $days)));
    }

    /**
     * Skip this occurrence: the task is planned again one interval from today
     * without counting as done.
     */
    public function skip(CareSchedule $schedule, DateTimeImmutable $today): void
    {
        $schedule->setNextDueOn($this->nextDueAfter($schedule, $today));
        $schedule->setSnoozedUntil(null);
    }
}
