<?php

namespace App\Tests\Functional;

use App\Entity\CareEvent;
use App\Enum\CareAction;
use App\Enum\CareTaskType;
use App\Tests\Support\Factory;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class PlantHistoryTest extends WebTestCase
{
    use Factory;

    public function testHistoryListsEventsNewestFirst(): void
    {
        $client = static::createClient();
        $user = $this->createUser(name: 'Mara');
        $plant = $this->createPlant($this->createHousehold($user), 'Monty');
        $this->em()->persist(new CareEvent($plant, CareTaskType::Water, CareAction::Done, new DateTimeImmutable('2026-03-01'), $user));
        $this->em()->persist(new CareEvent($plant, CareTaskType::Fertilize, CareAction::Skipped, new DateTimeImmutable('2026-03-10'), null));
        $this->em()->flush();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/plants/'.$plant->getId().'/history');

        $rows = $crawler->filter('.history-table tbody tr')->each(fn ($tr) => $tr->text());
        self::assertCount(2, $rows);
        self::assertStringContainsString('Tue 10 Mar 2026', $rows[0]);
        self::assertStringContainsString('Skipped', $rows[0]);
        self::assertStringContainsString('Mara', $rows[1]);
    }

    public function testHistoryEmptyState(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $plant = $this->createPlant($this->createHousehold($user));
        $client->loginUser($user);

        $client->request('GET', '/plants/'.$plant->getId().'/history');

        self::assertSelectorTextContains('.empty', 'Nothing logged yet');
    }
}
