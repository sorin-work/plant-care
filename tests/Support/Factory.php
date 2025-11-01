<?php

namespace App\Tests\Support;

use App\Entity\User;
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
}
