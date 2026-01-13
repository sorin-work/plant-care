<?php

namespace App\Repository;

use App\Entity\JournalEntry;
use App\Entity\Plant;
use App\Pagination\Page;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<JournalEntry>
 */
class JournalEntryRepository extends ServiceEntityRepository
{
    public const PER_PAGE = 10;

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

    /**
     * @return Page<JournalEntry>
     */
    public function pageForPlant(Plant $plant, int $page, int $perPage = self::PER_PAGE): Page
    {
        $qb = $this->createQueryBuilder('j')
            ->addSelect('a')
            ->leftJoin('j.author', 'a')
            ->where('j.plant = :plant')
            ->setParameter('plant', $plant)
            ->orderBy('j.writtenOn', 'DESC')
            ->addOrderBy('j.id', 'DESC');

        /** @var Page<JournalEntry> $result */
        $result = Page::fromQuery($qb, $page, $perPage);

        return $result;
    }
}
