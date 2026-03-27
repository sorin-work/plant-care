<?php

namespace App\Tests\Functional;

use App\Tests\Support\Factory;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class LoginTest extends WebTestCase
{
    use Factory;

    /**
     * A client with a clean login rate limiter, failed attempts are counted
     * in the cache across requests.
     */
    private function client(): KernelBrowser
    {
        $client = static::createClient();
        static::getContainer()->get('cache.rate_limiter')->clear();

        return $client;
    }

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
        self::assertSelectorTextContains('h1', 'Today');

        $client->request('GET', '/logout');
        $client->request('GET', '/');
        self::assertResponseRedirects('/login');
    }

    public function testWrongPasswordShowsError(): void
    {
        $client = $this->client();
        $this->createUser('ion@example.com', 'Ion');

        $client->request('GET', '/login');
        $client->submitForm('Sign in', ['email' => 'ion@example.com', 'password' => 'nope']);
        $client->followRedirect();

        self::assertSelectorTextContains('.flash-error', 'Invalid credentials');
    }

    public function testTooManyFailedAttemptsAreThrottled(): void
    {
        $client = $this->client();
        $this->createUser('ion@example.com', 'Ion');

        for ($i = 0; $i < 6; ++$i) {
            $client->request('GET', '/login');
            $client->submitForm('Sign in', ['email' => 'ion@example.com', 'password' => 'wrong-'.$i]);
        }
        $client->followRedirect();

        self::assertSelectorTextContains('.flash-error', 'Too many failed login attempts');
    }
}
