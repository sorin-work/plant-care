<?php

namespace App\Repository;

use App\Entity\Household;
use App\Entity\Invitation;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Invitation>
 */
class InvitationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Invitation::class);
    }

    public function findOneByToken(string $token): ?Invitation
    {
        return $this->findOneBy(['tokenHash' => Invitation::hash($token)]);
    }

    /**
     * @return list<Invitation>
     */
    public function findPending(Household $household, DateTimeImmutable $now): array
    {
        /** @var list<Invitation> $result */
        $result = $this->createQueryBuilder('i')
            ->where('i.household = :household')
            ->andWhere('i.acceptedAt IS NULL')
            ->andWhere('i.expiresAt > :now')
            ->setParameter('household', $household)
            ->setParameter('now', $now)
            ->orderBy('i.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        return $result;
    }
}
