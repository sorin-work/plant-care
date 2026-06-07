<?php

namespace App\Controller\Api;

use App\Care\DueTask;
use App\Entity\CareSchedule;

/**
 * Turns entities into the arrays returned by the JSON API.
 */
final class Presenter
{
    /**
     * @return array<string, mixed>
     */
    public static function task(DueTask $task): array
    {
        return self::schedule($task->schedule) + [
            'daysOverdue' => $task->daysOverdue,
            'plant' => [
                'id' => $task->plant()->getId(),
                'name' => $task->plant()->getName(),
                'room' => $task->plant()->getRoom()?->getName(),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function schedule(CareSchedule $schedule): array
    {
        return [
            'id' => $schedule->getId(),
            'type' => $schedule->getType()->value,
            'intervalDays' => $schedule->getIntervalDays(),
            'seasonal' => $schedule->isSeasonal(),
            'nextDueOn' => $schedule->getNextDueOn()->format('Y-m-d'),
            'lastDoneOn' => $schedule->getLastDoneOn()?->format('Y-m-d'),
            'snoozedUntil' => $schedule->getSnoozedUntil()?->format('Y-m-d'),
            'notes' => $schedule->getNotes(),
        ];
    }
}
