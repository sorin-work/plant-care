<?php

namespace App\Repository;

use App\Entity\Household;
use App\Entity\Plant;
use App\Entity\Room;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Room>
 */
class RoomRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Room::class);
    }

    /**
     * @return list<Room>
     */
    public function findForHousehold(Household $household): array
    {
        return $this->findBy(['household' => $household], ['name' => 'ASC']);
    }

    /**
     * Number of active plants per room id.
     *
     * @return array<int, int>
     */
    public function countPlants(Household $household): array
    {
        $rows = $this->getEntityManager()->createQueryBuilder()
            ->select('IDENTITY(p.room) AS room', 'COUNT(p.id) AS plants')
            ->from(Plant::class, 'p')
            ->where('p.household = :household')
            ->andWhere('p.archivedAt IS NULL')
            ->andWhere('p.room IS NOT NULL')
            ->groupBy('p.room')
            ->setParameter('household', $household)
            ->getQuery()
            ->getArrayResult();

        $counts = [];
        foreach ($rows as $row) {
            $counts[(int) $row['room']] = (int) $row['plants'];
        }

        return $counts;
    }
}
