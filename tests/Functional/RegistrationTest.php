<?php

namespace App\Tests\Functional;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class RegistrationTest extends WebTestCase
{
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
        $user = static::getContainer()->get(UserRepository::class)->findOneByEmail('ana@example.com');
        self::assertNotNull($user);
        self::assertSame('Ana', $user->getDisplayName());
        self::assertNotSame('monstera-123', $user->getPassword());
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
        foreach (['first', 'second'] as $name) {
            $client->request('GET', '/register');
            $client->submitForm('Create account', [
                'registration_form[displayName]' => $name,
                'registration_form[email]' => 'same@example.com',
                'registration_form[plainPassword]' => 'long-enough-1',
            ]);
        }

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('form', 'already an account');
    }
}
