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
}
