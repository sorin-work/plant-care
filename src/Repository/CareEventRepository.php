<?php

namespace App\Repository;

use App\Entity\CareEvent;
use App\Entity\Plant;
use App\Pagination\Page;
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
}
