<?php

namespace App\Household;

use App\Entity\Household;
use App\Entity\Invitation;
use App\Entity\Membership;
use App\Entity\User;
use App\Enum\HouseholdRole;
use App\Repository\MembershipRepository;
use App\Repository\UserRepository;
use DateTimeImmutable;
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
     * Creates an invitation link; returns the invitation and the secret token
     * that goes into the link (it is not stored).
     *
     * @return array{Invitation, string}
     */
    public function invite(Household $household, HouseholdRole $role, User $by): array
    {
        $token = bin2hex(random_bytes(20));
        $invitation = new Invitation($household, $role, $token, $by);
        $this->em->persist($invitation);
        $this->em->flush();

        return [$invitation, $token];
    }

    /**
     * @throws MembershipException
     */
    public function accept(Invitation $invitation, User $user, DateTimeImmutable $now): Membership
    {
        if (!$invitation->isUsable($now)) {
            throw new MembershipException('This invitation has expired or was already used.');
        }
        if (null !== $this->memberships->findOneFor($invitation->getHousehold(), $user)) {
            throw new MembershipException(\sprintf('You are already a member of %s.', $invitation->getHousehold()->getName()));
        }

        $membership = new Membership($invitation->getHousehold(), $user, $invitation->getRole());
        $invitation->markAccepted($now);
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
     * The user leaves the household. When nobody is left the household and
     * its plants are deleted.
     *
     * @throws MembershipException when the last owner leaves other members behind
     */
    public function leave(Membership $membership): void
    {
        $household = $membership->getHousehold();
        if (\count($this->memberships->findForHousehold($household)) > 1) {
            $this->ensureAnotherOwner($membership);
        }

        $household->removeMembership($membership);
        $this->em->remove($membership);

        if ($household->getMemberships()->isEmpty()) {
            $this->em->remove($household);
        }

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
