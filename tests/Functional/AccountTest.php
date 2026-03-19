<?php

namespace App\Tests\Functional;

use App\Entity\User;
use App\Tests\Support\Factory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class AccountTest extends WebTestCase
{
    use Factory;

    public function testCreateAndRevokeApiToken(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $this->createHousehold($user);
        $client->loginUser($user);

        $client->request('GET', '/account');
        $crawler = $client->submitForm('Create token');
        $token = $crawler->filter('.token-value')->text();
        self::assertStringStartsWith('pc_', $token);

        $reloaded = $this->em()->find(User::class, $user->getId());
        self::assertSame(hash('sha256', $token), $reloaded?->getApiTokenHash());

        $client->request('GET', '/account');
        self::assertSelectorNotExists('.token-value');
        $client->submitForm('Revoke');
        $client->followRedirect();
        self::assertSelectorTextContains('.flash', 'revoked');
        self::assertNull($this->em()->find(User::class, $user->getId())?->getApiTokenHash());
    }

    public function testCreateAndReplaceCalendarLink(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $this->createHousehold($user);
        $client->loginUser($user);

        $client->request('GET', '/account');
        $client->submitForm('Create calendar link');
        $crawler = $client->followRedirect();
        $first = $crawler->filter('.token-value')->first()->text();
        self::assertMatchesRegularExpression('#/calendar/[a-f0-9]{32}\.ics$#', $first);

        $client->submitForm('Replace calendar link');
        $crawler = $client->followRedirect();
        self::assertNotSame($first, $crawler->filter('.token-value')->first()->text());

        $client->request('GET', parse_url($first, \PHP_URL_PATH));
        self::assertResponseStatusCodeSame(404);
    }
}
