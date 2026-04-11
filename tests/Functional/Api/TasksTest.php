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

    public function testSkip(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $plant = $this->createPlant($this->createHousehold($user), 'Monty');
        $schedule = $this->schedule($plant, '-2 days');

        $client->request('POST', '/api/tasks/'.$schedule->getId().'/skip', server: $this->apiHeaders($user));

        self::assertResponseIsSuccessful();
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertNull($data['lastDoneOn']);
        self::assertSame((new DateTimeImmutable('today +5 days'))->format('Y-m-d'), $data['nextDueOn']);
    }

    public function testSnooze(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $schedule = $this->schedule($this->createPlant($this->createHousehold($user)));

        $client->request('POST', '/api/tasks/'.$schedule->getId().'/snooze', server: $this->apiHeaders($user) + ['CONTENT_TYPE' => 'application/json'], content: '{"days": 2}');

        self::assertResponseIsSuccessful();
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertSame((new DateTimeImmutable('today +2 days'))->format('Y-m-d'), $data['snoozedUntil']);
    }

    public function testSnoozeValidation(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $schedule = $this->schedule($this->createPlant($this->createHousehold($user)));
        $headers = $this->apiHeaders($user) + ['CONTENT_TYPE' => 'application/json'];

        $client->request('POST', '/api/tasks/'.$schedule->getId().'/snooze', server: $headers, content: '{"days": 40}');
        self::assertResponseStatusCodeSame(422);

        $client->request('POST', '/api/tasks/'.$schedule->getId().'/snooze', server: $headers, content: '{"days": "3"}');
        self::assertResponseStatusCodeSame(422);
    }

    public function testStrangerCannotSkipTasks(): void
    {
        $client = static::createClient();
        $schedule = $this->schedule($this->createPlant($this->createHousehold($this->createUser('owner@example.com'))), '-1 day');
        $before = $schedule->getNextDueOn()->format('Y-m-d');
        $stranger = $this->createUser('stranger@example.com');

        $client->request('POST', '/api/tasks/'.$schedule->getId().'/skip', server: $this->apiHeaders($stranger));

        self::assertResponseStatusCodeSame(404);
        $this->em()->clear();
        self::assertSame($before, $this->em()->find(CareSchedule::class, $schedule->getId())?->getNextDueOn()->format('Y-m-d'));
    }

    public function testViewerCannotSkipTasks(): void
    {
        $client = static::createClient();
        $owner = $this->createUser('owner@example.com');
        $household = $this->createHousehold($owner);
        $viewer = $this->createUser('viewer@example.com');
        $this->addMember($household, $viewer, HouseholdRole::Viewer);
        $schedule = $this->schedule($this->createPlant($household));

        $client->request('POST', '/api/tasks/'.$schedule->getId().'/skip', server: $this->apiHeaders($viewer));

        self::assertResponseStatusCodeSame(403);
        self::assertCount(0, static::getContainer()->get(CareEventRepository::class)->findRecentForPlant($schedule->getPlant()));
    }
}
