<?php

namespace App\Repository;

use App\Entity\Household;
use App\Entity\Plant;
use App\Entity\Room;
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
    public function findActive(Household $household, ?Room $room = null, ?string $search = null): array
    {
        // Cards show the room, the species and the next task: load them in
        // the same query instead of once per plant.
        $qb = $this->createQueryBuilder('p')
            ->addSelect('r', 's', 'sch')
            ->leftJoin('p.room', 'r')
            ->leftJoin('p.species', 's')
            ->leftJoin('p.schedules', 'sch')
            ->where('p.household = :household')
            ->andWhere('p.archivedAt IS NULL')
            ->setParameter('household', $household)
            ->orderBy('p.name', 'ASC');

        if (null !== $room) {
            $qb->andWhere('p.room = :room')->setParameter('room', $room);
        }

        $search = trim((string) $search);
        if ('' !== $search) {
            $qb->andWhere("LOWER(p.name) LIKE :q ESCAPE '!' OR LOWER(s.commonName) LIKE :q ESCAPE '!' OR LOWER(s.scientificName) LIKE :q ESCAPE '!'")
                ->setParameter('q', '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], mb_strtolower($search)).'%');
        }

        /** @var list<Plant> $result */
        $result = $qb->getQuery()->getResult();

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
