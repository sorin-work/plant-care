<?php

namespace App\Tests\Functional;

use App\Tests\Support\Factory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class LoginTest extends WebTestCase
{
    use Factory;

    public function testUserCanSignInAndOut(): void
    {
        $client = static::createClient();
        $hasher = static::getContainer()->get('security.user_password_hasher');
        $user = $this->createUser('ion@example.com', 'Ion');
        $user->setPassword($hasher->hashPassword($user, 'pothos-forever'));
        $this->em()->flush();

        $client->request('GET', '/login');
        $client->submitForm('Sign in', ['email' => 'ion@example.com', 'password' => 'pothos-forever']);
        self::assertResponseRedirects('http://localhost/');

        $client->followRedirect();
        self::assertSelectorTextContains('h1', 'Hello, Ion');

        $client->request('GET', '/logout');
        $client->request('GET', '/');
        self::assertResponseRedirects('/login');
    }

    public function testWrongPasswordShowsError(): void
    {
        $client = static::createClient();
        $this->createUser('ion@example.com', 'Ion');

        $client->request('GET', '/login');
        $client->submitForm('Sign in', ['email' => 'ion@example.com', 'password' => 'nope']);
        $client->followRedirect();

        self::assertSelectorTextContains('.flash-error', 'Invalid credentials');
    }
}
