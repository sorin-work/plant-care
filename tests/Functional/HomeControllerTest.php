<?php

namespace App\Tests\Functional;

use App\Tests\Support\Factory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class HomeControllerTest extends WebTestCase
{
    use Factory;

    public function testAnonymousVisitorIsSentToLogin(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        self::assertResponseRedirects('/login');
    }

    public function testSignedInUserIsGreeted(): void
    {
        $client = static::createClient();
        $client->loginUser($this->createUser(name: 'Mara'));
        $client->request('GET', '/');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Hello, Mara');
    }
}
