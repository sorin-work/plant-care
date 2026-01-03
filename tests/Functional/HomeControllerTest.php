<?php

namespace App\Tests\Functional;

use App\Entity\CareSchedule;
use App\Enum\CareTaskType;
use App\Tests\Support\Factory;
use DateTimeImmutable;
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

    public function testEmptyTodayList(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $this->createHousehold($user);
        $client->loginUser($user);

        $client->request('GET', '/');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Today');
        self::assertSelectorTextContains('.empty', 'All caught up');
    }

    public function testShowsDueAndOverdueTasksOnly(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $household = $this->createHousehold($user);
        $due = $this->createPlant($household, 'Due today');
        $late = $this->createPlant($household, 'Late');
        $later = $this->createPlant($household, 'Later');
        $archived = $this->createPlant($household, 'Archived');
        $archived->archive();
        $this->em()->persist(new CareSchedule($due, CareTaskType::Water, 7, new DateTimeImmutable('today')));
        $this->em()->persist(new CareSchedule($late, CareTaskType::Water, 7, new DateTimeImmutable('-3 days')));
        $this->em()->persist(new CareSchedule($later, CareTaskType::Water, 7, new DateTimeImmutable('+1 day')));
        $this->em()->persist(new CareSchedule($archived, CareTaskType::Water, 7, new DateTimeImmutable('-1 day')));
        $this->em()->flush();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/');

        self::assertSame(['Late', 'Due today'], $crawler->filter('.tasks li a')->each(fn ($a) => $a->text()));
    }

    public function testDoneFromTodayReturnsToToday(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $plant = $this->createPlant($this->createHousehold($user), 'Monty');
        $this->em()->persist(new CareSchedule($plant, CareTaskType::Water, 7, new DateTimeImmutable('today')));
        $this->em()->flush();
        $client->loginUser($user);

        $client->request('GET', '/');
        $client->submitForm('Done');
        self::assertResponseRedirects('/');
        $client->followRedirect();

        self::assertSelectorTextContains('.empty', 'All caught up');
    }

    public function testTasksAreGroupedByRoom(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $household = $this->createHousehold($user);
        $kitchen = $this->createRoom($household, 'Kitchen');
        $this->em()->persist(new CareSchedule($this->createPlant($household, 'Basil', $kitchen), CareTaskType::Water, 2, new DateTimeImmutable('today')));
        $this->em()->persist(new CareSchedule($this->createPlant($household, 'Stray'), CareTaskType::Water, 7, new DateTimeImmutable('today')));
        $this->em()->flush();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/');

        self::assertSame(['Kitchen (1)', 'No room (1)'], $crawler->filter('.room-group h2')->each(fn ($h) => $h->text()));
        self::assertSelectorTextContains('.page-header', '2 tasks');
    }

    public function testOverdueBadge(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $household = $this->createHousehold($user);
        $this->em()->persist(new CareSchedule($this->createPlant($household, 'One'), CareTaskType::Water, 7, new DateTimeImmutable('-1 day')));
        $this->em()->persist(new CareSchedule($this->createPlant($household, 'Four'), CareTaskType::Fertilize, 30, new DateTimeImmutable('-4 days')));
        $this->em()->persist(new CareSchedule($this->createPlant($household, 'Now'), CareTaskType::Water, 7, new DateTimeImmutable('today')));
        $this->em()->flush();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/');

        self::assertSame(['4 days late', '1 day late', 'due today'], $crawler->filter('.tasks .badge')->each(fn ($b) => $b->text()));
    }
}
