<?php

namespace App\Tests\Functional;

use App\Entity\CareSchedule;
use App\Enum\CareTaskType;
use App\Tests\Support\Factory;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Clock\Test\ClockSensitiveTrait;

final class UpcomingTest extends WebTestCase
{
    use ClockSensitiveTrait;
    use Factory;

    public function testGroupsUpcomingCareByDay(): void
    {
        static::mockTime('2026-03-15 10:00:00');
        $client = static::createClient();
        $user = $this->createUser();
        $household = $this->createHousehold($user);
        $this->em()->persist(new CareSchedule($this->createPlant($household, 'Fern'), CareTaskType::Water, 3, new DateTimeImmutable('2026-03-18')));
        $this->em()->persist(new CareSchedule($this->createPlant($household, 'Basil'), CareTaskType::Water, 2, new DateTimeImmutable('2026-03-16')));
        $this->em()->persist(new CareSchedule($this->createPlant($household, 'Today'), CareTaskType::Water, 2, new DateTimeImmutable('2026-03-15')));
        $this->em()->persist(new CareSchedule($this->createPlant($household, 'Far'), CareTaskType::Water, 30, new DateTimeImmutable('2026-04-15')));
        $this->em()->flush();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/upcoming');

        self::assertSame(['Mon 16 Mar', 'Wed 18 Mar'], $crawler->filter('.upcoming-day h2')->each(fn ($h) => $h->text()));
        self::assertSelectorTextContains('.upcoming-day', 'Basil');
        self::assertSelectorTextNotContains('main', 'Far');
    }

    public function testSnoozedTaskShowsOnItsNewDay(): void
    {
        static::mockTime('2026-03-15 10:00:00');
        $client = static::createClient();
        $user = $this->createUser();
        $household = $this->createHousehold($user);
        $schedule = (new CareSchedule($this->createPlant($household, 'Lily'), CareTaskType::Water, 5, new DateTimeImmutable('2026-03-14')))
            ->setSnoozedUntil(new DateTimeImmutable('2026-03-17'));
        $this->em()->persist($schedule);
        $this->em()->flush();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/upcoming');

        self::assertSame(['Tue 17 Mar'], $crawler->filter('.upcoming-day h2')->each(fn ($h) => $h->text()));
    }

    public function testEmptyState(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $this->createHousehold($user);
        $client->loginUser($user);

        $client->request('GET', '/upcoming');

        self::assertSelectorTextContains('.empty', 'Nothing planned');
    }
}
