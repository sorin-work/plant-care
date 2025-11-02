<?php

namespace App\Household;

use App\Entity\Household;
use App\Entity\Membership;
use App\Entity\User;
use App\Repository\MembershipRepository;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Resolves the household the signed-in user is currently working in.
 *
 * The choice is kept in the session; when it is missing or no longer valid
 * the user's oldest membership is used.
 */
final class CurrentHousehold
{
    private const SESSION_KEY = 'current_household_id';

    private ?Membership $resolved = null;

    public function __construct(
        private readonly Security $security,
        private readonly RequestStack $requestStack,
        private readonly MembershipRepository $memberships,
        private readonly HouseholdManager $manager,
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function get(): Household
    {
        return $this->membership()->getHousehold();
    }

    public function membership(): Membership
    {
        if (null !== $this->resolved) {
            return $this->resolved;
        }

        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new LogicException('There is no signed-in user.');
        }

        $all = $this->memberships->findForUser($user);
        if ([] === $all) {
            $household = $this->manager->create($user);
            $this->em->flush();
            $all = $this->memberships->findForUser($user);
            \assert([] !== $all, 'Household for '.$household->getName().' was not created');
        }

        $wanted = $this->session()?->get(self::SESSION_KEY);
        foreach ($all as $membership) {
            if ($membership->getHousehold()->getId() === $wanted) {
                return $this->resolved = $membership;
            }
        }

        return $this->resolved = $all[0];
    }

    /**
     * @return list<Membership>
     */
    public function all(): array
    {
        $user = $this->security->getUser();

        return $user instanceof User ? $this->memberships->findForUser($user) : [];
    }

    public function switchTo(Household $household): bool
    {
        foreach ($this->all() as $membership) {
            if ($membership->getHousehold()->getId() === $household->getId()) {
                $this->session()?->set(self::SESSION_KEY, $household->getId());
                $this->resolved = $membership;

                return true;
            }
        }

        return false;
    }

    private function session(): ?\Symfony\Component\HttpFoundation\Session\SessionInterface
    {
        $request = $this->requestStack->getMainRequest();

        return $request?->hasSession() ? $request->getSession() : null;
    }
}
