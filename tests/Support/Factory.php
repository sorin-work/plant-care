<?php

namespace App\Tests\Support;

use App\Entity\Household;
use App\Entity\Membership;
use App\Entity\User;
use App\Enum\HouseholdRole;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Small helpers to build entities in functional tests.
 */
trait Factory
{
    protected function em(): EntityManagerInterface
    {
        return static::getContainer()->get(EntityManagerInterface::class);
    }

    protected function createUser(string $email = 'demo@example.com', string $name = 'Demo'): User
    {
        $user = (new User())
            ->setEmail($email)
            ->setDisplayName($name)
            // "password" hashed with the cheap test hasher settings
            ->setPassword('$2y$04$Bq3G7xNCw3o2lGGMqMgsnuR6SbmNcnXXvwsv5yZ9fvLTUu6VAWQw2');

        $this->em()->persist($user);
        $this->em()->flush();

        return $user;
    }

    protected function createHousehold(User $owner, string $name = 'Home'): Household
    {
        $household = new Household($name);
        $this->em()->persist($household);
        $this->em()->persist(new Membership($household, $owner, HouseholdRole::Owner));
        $this->em()->flush();

        return $household;
    }

    protected function addMember(Household $household, User $user, HouseholdRole $role): Membership
    {
        $membership = new Membership($household, $user, $role);
        $this->em()->persist($membership);
        $this->em()->flush();

        return $membership;
    }
}
