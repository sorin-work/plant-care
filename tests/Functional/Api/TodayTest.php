<?php

namespace App\Tests\Functional\Api;

use App\Entity\CareSchedule;
use App\Enum\CareTaskType;
use App\Enum\HouseholdRole;
use App\Tests\Support\Factory;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class TodayTest extends WebTestCase
{
    use Factory;

    public function testListsDueTasks(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $household = $this->createHousehold($user);
        $room = $this->createRoom($household, 'Kitchen');
        $plant = $this->createPlant($household, 'Basil', $room);
        $schedule = new CareSchedule($plant, CareTaskType::Water, 2, new DateTimeImmutable('-1 day'));
        $this->em()->persist($schedule);
        $this->em()->persist(new CareSchedule($this->createPlant($household, 'Later'), CareTaskType::Water, 7, new DateTimeImmutable('+3 days')));
        $this->em()->flush();

        $client->request('GET', '/api/today', server: $this->apiHeaders($user));

        self::assertResponseIsSuccessful();
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertSame((new DateTimeImmutable('today'))->format('Y-m-d'), $data['date']);
        self::assertCount(1, $data['tasks']);
        self::assertSame($schedule->getId(), $data['tasks'][0]['id']);
        self::assertSame('water', $data['tasks'][0]['type']);
        self::assertSame(1, $data['tasks'][0]['daysOverdue']);
        self::assertSame(['id' => $plant->getId(), 'name' => 'Basil', 'room' => 'Kitchen'], $data['tasks'][0]['plant']);
    }

    public function testSelectsHouseholdByQuery(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $this->createHousehold($user, 'Mine');
        $office = $this->createHousehold($this->createUser('boss@example.com'), 'Office');
        $this->addMember($office, $user, HouseholdRole::Caretaker);
        $this->em()->persist(new CareSchedule($this->createPlant($office, 'Ficus'), CareTaskType::Water, 7, new DateTimeImmutable('today')));
        $this->em()->flush();

        $client->request('GET', '/api/today?household='.$office->getId(), server: $this->apiHeaders($user));

        $data = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertSame($office->getId(), $data['household']);
        self::assertSame('Ficus', $data['tasks'][0]['plant']['name']);
    }

    public function testForeignHouseholdIsNotFound(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $this->createHousehold($user);
        $other = $this->createHousehold($this->createUser('x@example.com'), 'Other');

        $client->request('GET', '/api/today?household='.$other->getId(), server: $this->apiHeaders($user));

        self::assertResponseStatusCodeSame(404);
    }
}
