<?php

namespace App\Tests\Functional\Api;

use App\Tests\Support\Factory;
use DateTimeImmutable;
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

    public function testShowsPlantWithSchedules(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $plant = $this->createPlant($this->createHousehold($user), 'Fern');
        $this->em()->persist(new \App\Entity\CareSchedule($plant, \App\Enum\CareTaskType::Water, 3, new DateTimeImmutable('2026-03-10')));
        $this->em()->flush();

        $client->request('GET', '/api/plants/'.$plant->getId(), server: $this->apiHeaders($user));

        $data = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertSame('Fern', $data['name']);
        self::assertSame('water', $data['schedules'][0]['type']);
        self::assertSame('2026-03-10', $data['schedules'][0]['nextDueOn']);
        self::assertSame([], $data['recentCare']);
    }

    public function testForeignPlantIsNotFound(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $this->createHousehold($user);
        $plant = $this->createPlant($this->createHousehold($this->createUser('x@example.com')), 'Theirs');

        $client->request('GET', '/api/plants/'.$plant->getId(), server: $this->apiHeaders($user));

        self::assertResponseStatusCodeSame(404);
    }
}
