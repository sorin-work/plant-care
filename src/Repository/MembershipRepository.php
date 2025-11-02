<?php

namespace App\Repository;

use App\Entity\Household;
use App\Entity\Membership;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Membership>
 */
class MembershipRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Membership::class);
    }

    public function findOneFor(Household $household, User $user): ?Membership
    {
        return $this->findOneBy(['household' => $household, 'user' => $user]);
    }

    /**
     * @return list<Membership>
     */
    public function findForUser(User $user): array
    {
        /** @var list<Membership> $result */
        $result = $this->createQueryBuilder('m')
            ->addSelect('h')
            ->join('m.household', 'h')
            ->where('m.user = :user')
            ->setParameter('user', $user)
            ->orderBy('m.joinedAt', 'ASC')
            ->addOrderBy('m.id', 'ASC')
            ->getQuery()
            ->getResult();

        return $result;
    }
}
