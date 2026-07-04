<?php

namespace App\Tests\Functional;

use App\Entity\CareSchedule;
use App\Enum\CareTaskType;
use App\Tests\Support\Factory;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class CareSheetTest extends WebTestCase
{
    use Factory;

    public function testCareSheetGroupsPlantsByRoom(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $household = $this->createHousehold($user, 'Flat');
        $plant = $this->createPlant($household, 'Monty', $this->createRoom($household, 'Living room'));
        $this->em()->persist((new CareSchedule($plant, CareTaskType::Water, 7, new DateTimeImmutable('2026-07-06')))->setSeasonal(false)->setNotes('500 ml'));
        $this->em()->persist(new CareSchedule($plant, CareTaskType::Repot, 540, new DateTimeImmutable('2027-01-01')));
        $this->createPlant($household, 'Stray');
        $client->loginUser($user);

        $crawler = $client->request('GET', '/household/care-sheet');

        self::assertResponseIsSuccessful();
        self::assertSame(['Living room', 'No room'], $crawler->filter('.care-sheet-room h2')->each(fn ($h) => $h->text()));
        self::assertSelectorTextContains('.care-sheet-room', 'Water every 7 days');
        self::assertSelectorTextContains('.care-sheet-room', 'Water: 500 ml');
        self::assertSelectorTextNotContains('.care-sheet-room', 'Repot');
    }
}
