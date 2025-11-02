<?php

namespace App\Household;

use App\Entity\Household;
use App\Entity\Membership;
use App\Entity\User;
use App\Enum\HouseholdRole;
use Doctrine\ORM\EntityManagerInterface;

final class HouseholdManager
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    /**
     * Creates a household owned by the given user. Does not flush.
     */
    public function create(User $owner, ?string $name = null): Household
    {
        $household = new Household($name ?? self::defaultName($owner));
        $this->em->persist($household);
        $this->em->persist(new Membership($household, $owner, HouseholdRole::Owner));

        return $household;
    }

    public static function defaultName(User $owner): string
    {
        $name = $owner->getDisplayName();

        return '' === $name ? 'My home' : \sprintf("%s's home", $name);
    }
}
