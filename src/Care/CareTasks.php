<?php

namespace App\Care;

use App\Entity\CareEvent;
use App\Entity\CareSchedule;
use App\Entity\User;
use App\Enum\CareAction;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Application service for the actions a user can take on a care task.
 */
final class CareTasks
{
    public function __construct(
        private readonly CareScheduler $scheduler,
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function markDone(CareSchedule $schedule, ?User $user, DateTimeImmutable $day): CareEvent
    {
        $this->scheduler->complete($schedule, $day);

        return $this->log($schedule, CareAction::Done, $user, $day);
    }

    public function skip(CareSchedule $schedule, ?User $user, DateTimeImmutable $today): CareEvent
    {
        $this->scheduler->skip($schedule, $today);

        return $this->log($schedule, CareAction::Skipped, $user, $today);
    }

    public function snooze(CareSchedule $schedule, ?User $user, int $days, DateTimeImmutable $today): CareEvent
    {
        $this->scheduler->snooze($schedule, $days, $today);

        return $this->log($schedule, CareAction::Snoozed, $user, $today);
    }

    private function log(CareSchedule $schedule, CareAction $action, ?User $user, DateTimeImmutable $day): CareEvent
    {
        $event = new CareEvent($schedule->getPlant(), $schedule->getType(), $action, $day, $user);
        $this->em->persist($event);
        $this->em->flush();

        return $event;
    }
}
