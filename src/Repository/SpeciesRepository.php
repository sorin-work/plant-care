<?php

namespace App\Repository;

use App\Entity\Species;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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

    public function findOneByScientificName(string $name): ?Species
    {
        return $this->findOneBy(['scientificName' => $name]);
    }

    /**
     * @return list<Species>
     */
    public function search(?string $query): array
    {
        $qb = $this->createQueryBuilder('s')->orderBy('s.commonName', 'ASC');
        $query = trim((string) $query);
        if ('' !== $query) {
            $qb->where('LOWER(s.commonName) LIKE :q OR LOWER(s.scientificName) LIKE :q')
                ->setParameter('q', '%'.mb_strtolower($query).'%');
        }

        /** @var list<Species> $result */
        $result = $qb->getQuery()->getResult();

        return $result;
    }
}
