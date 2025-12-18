<?php

namespace App\Care;

use App\Entity\CareSchedule;
use App\Entity\Plant;

/**
 * A schedule that needs attention on a given day.
 */
final readonly class DueTask
{
    public function __construct(
        public CareSchedule $schedule,
        public int $daysOverdue,
    ) {
    }

    public function plant(): Plant
    {
        return $this->schedule->getPlant();
    }

    public function isOverdue(): bool
    {
        return $this->daysOverdue > 0;
    }
}
