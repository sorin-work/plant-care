<?php

namespace App\Care;

use App\Entity\CareSchedule;
use App\Entity\Plant;
use App\Enum\CareTaskType;
use DateTimeImmutable;

/**
 * Creates the initial schedules for a new plant, using its species defaults
 * when it has one.
 */
final class ScheduleFactory
{
    public const DEFAULT_WATER_DAYS = 7;
    public const DEFAULT_REPOT_MONTHS = 18;

    /**
     * @return list<CareSchedule>
     */
    public function createDefaults(Plant $plant, DateTimeImmutable $today): array
    {
        $today = $today->setTime(0, 0);
        $species = $plant->getSpecies();

        // Water right away: we don't know when it was last watered.
        $schedules = [
            new CareSchedule($plant, CareTaskType::Water, $species?->getWaterIntervalDays() ?? self::DEFAULT_WATER_DAYS, $today),
        ];

        $fertilizeDays = $species?->getFertilizeIntervalDays();
        if (null !== $fertilizeDays) {
            $schedules[] = new CareSchedule($plant, CareTaskType::Fertilize, $fertilizeDays, $today->modify(\sprintf('+%d days', $fertilizeDays)));
        }

        $repotDays = 30 * ($species?->getRepotIntervalMonths() ?? self::DEFAULT_REPOT_MONTHS);
        $repotFrom = $plant->getAcquiredOn() ?? $today;
        $repotDue = $repotFrom->modify(\sprintf('+%d days', $repotDays));
        $schedules[] = (new CareSchedule($plant, CareTaskType::Repot, $repotDays, max($repotDue, $today)))->setSeasonal(false);

        return $schedules;
    }
}
