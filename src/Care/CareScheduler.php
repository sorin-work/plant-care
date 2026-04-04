<?php

namespace App\Care;

use App\Entity\CareSchedule;
use App\Enum\CareTaskType;
use App\Enum\Hemisphere;
use App\Enum\Season;
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
        $next = $day->setTime(0, 0)->modify(\sprintf('+%d days', $this->intervalOn($schedule, $day)));

        $household = $schedule->getPlant()->getHousehold();
        if (CareTaskType::Fertilize === $schedule->getType() && $schedule->isSeasonal() && $household->isPauseFertilizingInWinter()) {
            $next = $this->skipWinter($next, $household->getHemisphere());
        }

        return $next;
    }

    /**
     * Moves a date that falls in winter to the first day of spring.
     */
    private function skipWinter(DateTimeImmutable $date, Hemisphere $hemisphere): DateTimeImmutable
    {
        if (Season::Winter !== $this->seasons->seasonOn($date, $hemisphere)) {
            return $date;
        }

        $springMonth = Hemisphere::North === $hemisphere ? 3 : 9;
        $year = (int) $date->format('Y');
        if ((int) $date->format('n') > $springMonth) {
            ++$year;
        }

        return $date->setDate($year, $springMonth, 1);
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
