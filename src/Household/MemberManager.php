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

    /**
     * @throws MembershipException
     */
    public function changeRole(Membership $membership, HouseholdRole $role): void
    {
        if (HouseholdRole::Owner !== $role) {
            $this->ensureAnotherOwner($membership);
        }

        $membership->setRole($role);
        $this->em->flush();
    }

    /**
     * @throws MembershipException
     */
    public function remove(Membership $membership): void
    {
        $this->ensureAnotherOwner($membership);

        $membership->getHousehold()->removeMembership($membership);
        $this->em->remove($membership);
        $this->em->flush();
    }

    /**
     * A household must always keep at least one owner.
     */
    private function ensureAnotherOwner(Membership $membership): void
    {
        if (HouseholdRole::Owner !== $membership->getRole()) {
            return;
        }

        foreach ($this->memberships->findForHousehold($membership->getHousehold()) as $other) {
            if ($other->getId() !== $membership->getId() && HouseholdRole::Owner === $other->getRole()) {
                return;
            }
        }

        throw new MembershipException('A household needs at least one owner. Make someone else an owner first.');
    }
}
