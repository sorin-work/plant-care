<?php

namespace App\Tests\Functional;

use App\Entity\CareEvent;
use App\Enum\CareAction;
use App\Enum\CareTaskType;
use App\Stats\CareStats;
use App\Tests\Support\Factory;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class StatsTest extends WebTestCase
{
    use Factory;

    public function testCountsCarePerWeekTypeAndPlant(): void
    {
        static::createClient();
        $user = $this->createUser();
        $household = $this->createHousehold($user);
        $monty = $this->createPlant($household, 'Monty');
        $fern = $this->createPlant($household, 'Fern');
        $log = function ($plant, CareTaskType $type, CareAction $action, string $day): void {
            $this->em()->persist(new CareEvent($plant, $type, $action, new DateTimeImmutable($day)));
        };
        $log($monty, CareTaskType::Water, CareAction::Done, '2026-04-01');
        $log($monty, CareTaskType::Water, CareAction::Done, '2026-03-25');
        $log($fern, CareTaskType::Water, CareAction::Done, '2026-04-02');
        $log($fern, CareTaskType::Fertilize, CareAction::Skipped, '2026-04-02');
        $log($fern, CareTaskType::Water, CareAction::Done, '2025-12-01');
        $log($monty, CareTaskType::Water, CareAction::Snoozed, '2026-04-03');
        $this->em()->flush();

        $stats = static::getContainer()->get(CareStats::class)->forHousehold($household, new DateTimeImmutable('2026-04-04'));

        self::assertSame(3, $stats['total']);
        self::assertSame(['fertilize' => ['done' => 0, 'skipped' => 1], 'water' => ['done' => 3, 'skipped' => 0]], $stats['byType']);
        self::assertCount(CareStats::WEEKS, $stats['weeks']);
        self::assertSame('2026-03-30', $stats['weeks'][7]['start']->format('Y-m-d'));
        self::assertSame(2, $stats['weeks'][7]['done']);
        self::assertSame(1, $stats['weeks'][6]['done']);
        self::assertSame(['Monty', 'Fern'], array_column($stats['topPlants'], 'name'));
    }

    public function testStatsPageEmptyState(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $this->createHousehold($user);
        $client->loginUser($user);

        $client->request('GET', '/stats');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.empty', 'No care logged');
    }

    public function testStatsPageRenders(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $household = $this->createHousehold($user);
        $this->em()->persist(new CareEvent($this->createPlant($household, 'Monty'), CareTaskType::Water, CareAction::Done, new DateTimeImmutable('today')));
        $this->em()->flush();
        $client->loginUser($user);

        $client->request('GET', '/stats');

        self::assertSelectorCount(CareStats::WEEKS, '.bars li');
        self::assertSelectorTextContains('.stats-by-type', 'Water');
        self::assertSelectorTextContains('.top-plants', 'Monty');
    }
}
