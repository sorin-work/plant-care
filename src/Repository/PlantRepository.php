<?php

namespace App\Repository;

use App\Entity\Household;
use App\Entity\Plant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Plant>
 */
class PlantRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Plant::class);
    }

    /**
     * @return list<Plant>
     */
    public function findActive(Household $household): array
    {
        /** @var list<Plant> $result */
        $result = $this->createQueryBuilder('p')
            ->where('p.household = :household')
            ->andWhere('p.archivedAt IS NULL')
            ->setParameter('household', $household)
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();

        return $result;
    }

    /**
     * @return list<Plant>
     */
    public function findArchived(Household $household): array
    {
        /** @var list<Plant> $result */
        $result = $this->createQueryBuilder('p')
            ->where('p.household = :household')
            ->andWhere('p.archivedAt IS NOT NULL')
            ->setParameter('household', $household)
            ->orderBy('p.archivedAt', 'DESC')
            ->getQuery()
            ->getResult();

        return $result;
    }
}
