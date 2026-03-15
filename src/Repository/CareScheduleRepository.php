<?php

namespace App\Repository;

use App\Entity\CareSchedule;
use App\Entity\Household;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CareSchedule>
 */
class CareScheduleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CareSchedule::class);
    }

    /**
     * Schedules of active plants that are due on or before the day and not snoozed past it.
     *
     * @return list<CareSchedule>
     */
    public function findDue(Household $household, DateTimeImmutable $day): array
    {
        /** @var list<CareSchedule> $result */
        $result = $this->createQueryBuilder('s')
            ->join('s.plant', 'p')
            ->where('p.household = :household')
            ->andWhere('p.archivedAt IS NULL')
            ->andWhere('s.nextDueOn <= :day')
            ->andWhere('s.snoozedUntil IS NULL OR s.snoozedUntil <= :day')
            ->setParameter('household', $household)
            ->setParameter('day', $day->format('Y-m-d'))
            ->orderBy('s.nextDueOn', 'ASC')
            ->addOrderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();

        return $result;
    }

    /**
     * Schedules of active plants that come due after $from and up to $until.
     *
     * @return list<CareSchedule>
     */
    public function findComingUp(Household $household, DateTimeImmutable $from, DateTimeImmutable $until): array
    {
        /** @var list<CareSchedule> $result */
        $result = $this->createQueryBuilder('s')
            ->addSelect('p')
            ->join('s.plant', 'p')
            ->where('p.household = :household')
            ->andWhere('p.archivedAt IS NULL')
            ->andWhere('(s.nextDueOn > :from AND s.nextDueOn <= :until) OR (s.snoozedUntil > :from AND s.snoozedUntil <= :until)')
            ->setParameter('household', $household)
            ->setParameter('from', $from->format('Y-m-d'))
            ->setParameter('until', $until->format('Y-m-d'))
            ->getQuery()
            ->getResult();

        return $result;
    }
}
