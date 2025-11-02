<?php

namespace App\Tests\Functional;

use App\Enum\HouseholdRole;
use App\Tests\Support\Factory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class CurrentHouseholdTest extends WebTestCase
{
    use Factory;

    public function testUserWithoutHouseholdGetsOneCreated(): void
    {
        $client = static::createClient();
        $client->loginUser($this->createUser(name: 'Lia'));
        $client->request('GET', '/');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.site-nav', "Lia's home");
    }

    public function testUserCanSwitchBetweenHouseholds(): void
    {
        $client = static::createClient();
        $me = $this->createUser('me@example.com', 'Me');
        $this->createHousehold($me, 'Flat');
        $friend = $this->createUser('friend@example.com', 'Friend');
        $office = $this->createHousehold($friend, 'Office');
        $this->addMember($office, $me, HouseholdRole::Editor);

        $client->loginUser($me);
        $crawler = $client->request('GET', '/');
        self::assertSelectorTextContains('.household-switch option[selected]', 'Flat');

        $token = $crawler->filter('.household-switch input[name=_token]')->attr('value');
        $client->request('POST', '/household/'.$office->getId().'/switch', ['_token' => $token]);
        self::assertResponseRedirects('/');

        $client->followRedirect();
        self::assertSelectorTextContains('.household-switch option[selected]', 'Office');
    }

    public function testCannotSwitchToForeignHousehold(): void
    {
        $client = static::createClient();
        $me = $this->createUser('me@example.com', 'Me');
        $this->createHousehold($me, 'Flat');
        $stranger = $this->createUser('stranger@example.com', 'Stranger');
        $other = $this->createHousehold($stranger, 'Elsewhere');

        $client->loginUser($me);
        $crawler = $client->request('GET', '/');
        $client->request('POST', '/household/'.$other->getId().'/switch', [
            '_token' => $crawler->filter('input[name=_token]')->count() ? $crawler->filter('input[name=_token]')->attr('value') : 'x',
        ]);

        self::assertResponseStatusCodeSame(403);
    }
}
