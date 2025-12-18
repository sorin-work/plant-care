<?php

namespace App\Tests\Functional;

use App\Enum\HouseholdRole;
use App\Repository\MembershipRepository;
use App\Repository\UserRepository;
use App\Tests\Support\Factory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class RegistrationTest extends WebTestCase
{
    use Factory;

    public function testUserCanRegister(): void
    {
        $client = static::createClient();
        $client->request('GET', '/register');
        self::assertResponseIsSuccessful();

        $client->submitForm('Create account', [
            'registration_form[displayName]' => 'Ana',
            'registration_form[email]' => 'Ana@Example.com ',
            'registration_form[plainPassword]' => 'monstera-123',
        ]);

        self::assertResponseRedirects('/');
        $client->followRedirect();
        self::assertSelectorTextContains('h1', 'Today');
        $user = static::getContainer()->get(UserRepository::class)->findOneByEmail('ana@example.com');
        self::assertNotNull($user);
        self::assertSame('Ana', $user->getDisplayName());
        self::assertNotSame('monstera-123', $user->getPassword());

        $memberships = static::getContainer()->get(MembershipRepository::class)->findForUser($user);
        self::assertCount(1, $memberships);
        self::assertSame("Ana's home", $memberships[0]->getHousehold()->getName());
        self::assertSame(HouseholdRole::Owner, $memberships[0]->getRole());
    }

    public function testShortPasswordIsRejected(): void
    {
        $client = static::createClient();
        $client->request('GET', '/register');
        $client->submitForm('Create account', [
            'registration_form[displayName]' => 'Ana',
            'registration_form[email]' => 'ana@example.com',
            'registration_form[plainPassword]' => 'short',
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('form', 'at least 8 characters');
    }

    public function testDuplicateEmailIsRejected(): void
    {
        $client = static::createClient();
        $this->createUser('same@example.com', 'First');

        $client->request('GET', '/register');
        $client->submitForm('Create account', [
            'registration_form[displayName]' => 'Second',
            'registration_form[email]' => 'Same@example.com',
            'registration_form[plainPassword]' => 'long-enough-1',
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('form', 'already an account');
    }
}
