<?php

namespace App\Repository;

use App\Entity\JournalEntry;
use App\Entity\Plant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<JournalEntry>
 */
class JournalEntryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, JournalEntry::class);
    }

    /**
     * @return list<JournalEntry>
     */
    public function findLatestForPlant(Plant $plant, int $limit = 5): array
    {
        return $this->findBy(['plant' => $plant], ['writtenOn' => 'DESC', 'id' => 'DESC'], $limit);
    }
}
