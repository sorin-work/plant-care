<?php

namespace App\Repository;

use App\Entity\Household;
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
}
