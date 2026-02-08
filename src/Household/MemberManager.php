<?php

namespace App\Household;

use App\Entity\Household;
use App\Entity\Membership;
use App\Enum\HouseholdRole;
use App\Repository\MembershipRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

final class MemberManager
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly MembershipRepository $memberships,
        private readonly EntityManagerInterface $em,
    ) {
    }

    /**
     * Adds an existing user, found by email, to the household.
     *
     * @throws MembershipException
     */
    public function add(Household $household, string $email, HouseholdRole $role): Membership
    {
        $user = $this->users->findOneByEmail($email);
        if (null === $user) {
            throw new MembershipException('There is no account with this email yet. Ask them to sign up first.');
        }
        if (null !== $this->memberships->findOneFor($household, $user)) {
            throw new MembershipException(\sprintf('%s is already a member.', $user->getDisplayName()));
        }

        $membership = new Membership($household, $user, $role);
        $this->em->persist($membership);
        $this->em->flush();

        return $membership;
    }
}
