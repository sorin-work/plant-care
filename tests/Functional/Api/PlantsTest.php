<?php

namespace App\Tests\Functional\Api;

use App\Tests\Support\Factory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class PlantsTest extends WebTestCase
{
    use Factory;

    public function testListsActivePlants(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $household = $this->createHousehold($user);
        $this->createPlant($household, 'Monty the monstera', $this->createRoom($household, 'Office'));
        $gone = $this->createPlant($household, 'Gone');
        $gone->archive();
        $this->em()->flush();

        $client->request('GET', '/api/plants', server: $this->apiHeaders($user));

        self::assertResponseIsSuccessful();
        $plants = json_decode((string) $client->getResponse()->getContent(), true)['plants'];
        self::assertCount(1, $plants);
        self::assertSame('Monty the monstera', $plants[0]['name']);
        self::assertSame('MT', $plants[0]['initials']);
        self::assertSame('Office', $plants[0]['room']);
        self::assertSame('medium', $plants[0]['light']);
        self::assertFalse($plants[0]['hasPhoto']);
    }
}
