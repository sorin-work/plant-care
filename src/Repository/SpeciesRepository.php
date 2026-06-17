<?php

namespace App\Repository;

use App\Entity\Household;
use App\Entity\Species;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Species>
 */
class SpeciesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Species::class);
    }

    /**
     * Looks up a species of the shared catalog.
     */
    public function findOneByScientificName(string $name): ?Species
    {
        return $this->findOneBy(['scientificName' => $name, 'household' => null]);
    }

    /**
     * The shared catalog plus the species the household added.
     */
    public function availableTo(Household $household): QueryBuilder
    {
        return $this->createQueryBuilder('s')
            ->where('s.household IS NULL OR s.household = :household')
            ->setParameter('household', $household)
            ->orderBy('s.commonName', 'ASC');
    }

    /**
     * @return list<Species>
     */
    public function search(?string $query, Household $household): array
    {
        $qb = $this->availableTo($household);
        $query = trim((string) $query);
        if ('' !== $query) {
            $qb->andWhere('LOWER(s.commonName) LIKE :q OR LOWER(s.scientificName) LIKE :q')
                ->setParameter('q', '%'.mb_strtolower($query).'%');
        }

        /** @var list<Species> $result */
        $result = $qb->getQuery()->getResult();

        return $result;
    }
}
