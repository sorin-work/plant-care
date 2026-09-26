<?php

namespace App\Tests\Functional\Api;

use App\Tests\Support\Factory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class MeTest extends WebTestCase
{
    use Factory;

    public function testRequiresToken(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/me');

        self::assertResponseStatusCodeSame(401);
    }

    public function testRejectsUnknownToken(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/me', server: ['HTTP_AUTHORIZATION' => 'Bearer pc_nope']);

        self::assertResponseStatusCodeSame(401);
    }

    public function testReturnsUserAndHouseholds(): void
    {
        $client = static::createClient();
        $user = $this->createUser('ana@example.com', 'Ana');
        $household = $this->createHousehold($user, 'Flat');
        $client->request('GET', '/api/me', server: $this->apiHeaders($user));

        self::assertResponseIsSuccessful();
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertSame('ana@example.com', $data['email']);
        self::assertSame([['id' => $household->getId(), 'name' => 'Flat', 'role' => 'owner', 'timezone' => 'UTC', 'hemisphere' => 'north']], $data['households']);
    }

    public function testRequestsAreRateLimitedPerToken(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $this->createHousehold($user);
        $headers = $this->apiHeaders($user);

        for ($i = 0; $i < 10; ++$i) {
            $client->request('GET', '/api/me', server: $headers);
            self::assertResponseIsSuccessful();
        }
        self::assertResponseHeaderSame('x-ratelimit-remaining', '0');

        $client->request('GET', '/api/me', server: $headers);
        self::assertResponseStatusCodeSame(429);
        self::assertTrue($client->getResponse()->headers->has('retry-after'));
    }

    public function testListsEveryHouseholdWithTheRole(): void
    {
        $client = static::createClient();
        $user = $this->createUser('ana@example.com', 'Ana');
        $this->createHousehold($user, 'Mine');
        $office = $this->createHousehold($this->createUser('boss@example.com'), 'Office');
        $this->addMember($office, $user, \App\Enum\HouseholdRole::Caretaker);

        $client->request('GET', '/api/me', server: $this->apiHeaders($user));

        $households = json_decode((string) $client->getResponse()->getContent(), true)['households'];
        self::assertSame(['Mine' => 'owner', 'Office' => 'caretaker'], array_column($households, 'role', 'name'));
    }

    public function testRevokedTokenStopsWorking(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $headers = $this->apiHeaders($user);
        $user->revokeApiToken();
        $this->em()->flush();

        $client->request('GET', '/api/me', server: $headers);

        self::assertResponseStatusCodeSame(401);
    }
}
