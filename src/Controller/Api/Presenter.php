<?php

namespace App\Controller\Api;

use App\Care\DueTask;
use App\Entity\CareEvent;
use App\Entity\CareSchedule;
use App\Entity\Plant;
use App\Util\Initials;

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

    /**
     * @return array<string, mixed>
     */
    public static function plantSummary(Plant $plant): array
    {
        return [
            'id' => $plant->getId(),
            'name' => $plant->getName(),
            'initials' => Initials::of($plant->getName()),
            'species' => $plant->getSpecies()?->getScientificName(),
            'room' => $plant->getRoom()?->getName(),
            'light' => $plant->getLight()->value,
            'acquiredOn' => $plant->getAcquiredOn()?->format('Y-m-d'),
            'hasPhoto' => null !== $plant->getCoverPhoto(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function careEvent(CareEvent $event): array
    {
        return [
            'type' => $event->getType()->value,
            'action' => $event->getAction()->value,
            'on' => $event->getHappenedOn()->format('Y-m-d'),
            'by' => $event->getUser()?->getDisplayName(),
        ];
    }
}
