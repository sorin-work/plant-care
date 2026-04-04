<?php

namespace App\Repository;

use App\Entity\CareEvent;
use App\Entity\Household;
use App\Entity\Plant;
use App\Pagination\Page;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CareEvent>
 */
class CareEventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CareEvent::class);
    }

    /**
     * @return list<CareEvent>
     */
    public function findRecentForPlant(Plant $plant, int $limit = 10): array
    {
        return $this->findBy(['plant' => $plant], ['happenedOn' => 'DESC', 'id' => 'DESC'], $limit);
    }

    /**
     * @return Page<CareEvent>
     */
    public function pageForPlant(Plant $plant, int $page, int $perPage = 25): Page
    {
        $qb = $this->createQueryBuilder('e')
            ->addSelect('u')
            ->leftJoin('e.user', 'u')
            ->where('e.plant = :plant')
            ->setParameter('plant', $plant)
            ->orderBy('e.happenedOn', 'DESC')
            ->addOrderBy('e.id', 'DESC');

        /** @var Page<CareEvent> $result */
        $result = Page::fromQuery($qb, $page, $perPage);

        return $result;
    }

    /**
     * @return list<CareEvent>
     */
    public function findForHouseholdBetween(Household $household, DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        /** @var list<CareEvent> $result */
        $result = $this->createQueryBuilder('e')
            ->addSelect('p')
            ->join('e.plant', 'p')
            ->where('p.household = :household')
            ->andWhere('e.happenedOn >= :from AND e.happenedOn <= :to')
            ->setParameter('household', $household)
            ->setParameter('from', $from->format('Y-m-d'))
            ->setParameter('to', $to->format('Y-m-d'))
            ->orderBy('e.happenedOn', 'ASC')
            ->getQuery()
            ->getResult();

        return $result;
    }
}
