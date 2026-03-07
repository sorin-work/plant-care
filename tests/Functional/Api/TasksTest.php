<?php

namespace App\Tests\Functional\Api;

use App\Entity\CareSchedule;
use App\Entity\Plant;
use App\Enum\CareTaskType;
use App\Enum\HouseholdRole;
use App\Repository\CareEventRepository;
use App\Tests\Support\Factory;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class TasksTest extends WebTestCase
{
    use Factory;

    private function schedule(Plant $plant, string $due = 'today'): CareSchedule
    {
        $schedule = (new CareSchedule($plant, CareTaskType::Water, 5, new DateTimeImmutable($due)))->setSeasonal(false);
        $this->em()->persist($schedule);
        $this->em()->flush();

        return $schedule;
    }

    public function testMarkDone(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $plant = $this->createPlant($this->createHousehold($user), 'Monty');
        $schedule = $this->schedule($plant);

        $client->request('POST', '/api/tasks/'.$schedule->getId().'/done', server: $this->apiHeaders($user));

        self::assertResponseIsSuccessful();
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertSame((new DateTimeImmutable('today'))->format('Y-m-d'), $data['lastDoneOn']);
        self::assertSame((new DateTimeImmutable('today +5 days'))->format('Y-m-d'), $data['nextDueOn']);
        self::assertCount(1, static::getContainer()->get(CareEventRepository::class)->findRecentForPlant($plant));
    }

    public function testViewerCannotMarkDone(): void
    {
        $client = static::createClient();
        $owner = $this->createUser('owner@example.com');
        $household = $this->createHousehold($owner);
        $viewer = $this->createUser('viewer@example.com');
        $this->addMember($household, $viewer, HouseholdRole::Viewer);
        $schedule = $this->schedule($this->createPlant($household));

        $client->request('POST', '/api/tasks/'.$schedule->getId().'/done', server: $this->apiHeaders($viewer));

        self::assertResponseStatusCodeSame(403);
    }

    public function testStrangerGets404(): void
    {
        $client = static::createClient();
        $schedule = $this->schedule($this->createPlant($this->createHousehold($this->createUser('owner@example.com'))));
        $stranger = $this->createUser('stranger@example.com');

        $client->request('POST', '/api/tasks/'.$schedule->getId().'/done', server: $this->apiHeaders($stranger));

        self::assertResponseStatusCodeSame(404);
    }
}
