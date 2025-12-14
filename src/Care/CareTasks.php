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
        $event = new CareEvent($schedule->getPlant(), $schedule->getType(), CareAction::Done, $day, $user);
        $this->em->persist($event);
        $this->em->flush();

        return $event;
    }
}
