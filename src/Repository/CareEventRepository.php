<?php

namespace App\Repository;

use App\Entity\CareEvent;
use App\Entity\Plant;
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
}
