<?php

namespace App\Care;

use App\Entity\CareSchedule;
use App\Entity\JournalEntry;
use App\Entity\Plant;
use App\Entity\User;
use App\Enum\CareTaskType;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Creates a new plant from a cutting or offset of an existing one.
 */
final class Propagator
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    public function propagate(Plant $parent, ?User $user, DateTimeImmutable $today): Plant
    {
        $today = $today->setTime(0, 0);
        $baby = (new Plant($parent->getHousehold()))
            ->setName(mb_substr($parent->getName().' (cutting)', 0, 80))
            ->setSpecies($parent->getSpecies())
            ->setRoom($parent->getRoom())
            ->setLight($parent->getLight())
            ->setAcquiredOn($today)
            ->setNotes(\sprintf('Propagated from %s on %s.', $parent->getName(), $today->format('j M Y')));

        foreach ($parent->getSchedules() as $schedule) {
            if (CareTaskType::Repot === $schedule->getType()) {
                continue;
            }
            $first = CareTaskType::Water === $schedule->getType() ? $today : $today->modify(\sprintf('+%d days', $schedule->getIntervalDays()));
            (new CareSchedule($baby, $schedule->getType(), $schedule->getIntervalDays(), $first))->setSeasonal($schedule->isSeasonal());
        }

        $this->em->persist($baby);
        $this->em->persist((new JournalEntry($parent, $user, $today))->setNote(\sprintf('Took a cutting: %s.', $baby->getName())));
        $this->em->flush();

        return $baby;
    }
}
