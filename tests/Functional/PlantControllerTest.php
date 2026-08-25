<?php

namespace App\Tests\Functional;

use App\Entity\Species;
use App\Enum\LightLevel;
use App\Repository\PlantRepository;
use App\Tests\Support\Factory;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class PlantControllerTest extends WebTestCase
{
    use Factory;

    public function testEmptyPlantList(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $this->createHousehold($user);
        $client->loginUser($user);

        $client->request('GET', '/plants');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.empty', 'No plants yet');
    }

    public function testListsOnlyPlantsOfCurrentHousehold(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $household = $this->createHousehold($user);
        $room = $this->createRoom($household, 'Office');
        $this->createPlant($household, 'Monty', $room);
        $this->createPlant($household, 'Basil');
        $this->createPlant($this->createHousehold($this->createUser('x@example.com')), 'Not mine');
        $client->loginUser($user);

        $crawler = $client->request('GET', '/plants');

        self::assertSame(['Basil', 'Monty'], $crawler->filter('.plant-card h2')->each(fn ($n) => $n->text()));
        self::assertSelectorTextContains('.plant-grid', 'Office');
    }

    public function testAddPlant(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $household = $this->createHousehold($user);
        $room = $this->createRoom($household, 'Bedroom');
        $species = new Species('Snake plant', 'Dracaena trifasciata', 14, LightLevel::Low);
        $this->em()->persist($species);
        $this->em()->flush();
        $client->loginUser($user);

        $client->request('GET', '/plants/new');
        $client->submitForm('Add plant', [
            'plant[name]' => 'Sansa',
            'plant[species]' => (string) $species->getId(),
            'plant[room]' => (string) $room->getId(),
            'plant[light]' => 'low',
            'plant[acquiredOn]' => '2025-03-01',
        ]);
        self::assertResponseRedirects();
        $client->followRedirect();
        self::assertSelectorTextContains('h1', 'Sansa');
        self::assertSelectorTextContains('.facts', 'Bedroom');

        $plants = static::getContainer()->get(PlantRepository::class)->findActive($household);
        self::assertCount(1, $plants);
        self::assertSame('Sansa', $plants[0]->getName());
        self::assertSame('Bedroom', $plants[0]->getRoom()?->getName());
        self::assertSame('2025-03-01', $plants[0]->getAcquiredOn()?->format('Y-m-d'));
        self::assertCount(2, $plants[0]->getSchedules(), 'snake plant has no fertilizer default here');
    }

    public function testPlantNeedsAName(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $this->createHousehold($user);
        $client->loginUser($user);

        $client->request('GET', '/plants/new');
        $client->submitForm('Add plant', ['plant[name]' => '']);

        self::assertResponseStatusCodeSame(422);
    }

    public function testRoomOfAnotherHouseholdIsNotOffered(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $this->createHousehold($user);
        $foreignRoom = $this->createRoom($this->createHousehold($this->createUser('x@example.com')), 'Foreign');
        $client->loginUser($user);

        $crawler = $client->request('GET', '/plants/new');

        self::assertCount(0, $crawler->filter('#plant_room option[value="'.$foreignRoom->getId().'"]'));
    }

    public function testEditPlant(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $plant = $this->createPlant($this->createHousehold($user), 'Monty');
        $client->loginUser($user);

        $client->request('GET', '/plants/'.$plant->getId().'/edit');
        $client->submitForm('Save', ['plant[name]' => 'Monty II', 'plant[light]' => 'direct']);
        self::assertResponseRedirects('/plants/'.$plant->getId());

        $reloaded = static::getContainer()->get(PlantRepository::class)->find($plant->getId());
        self::assertSame('Monty II', $reloaded?->getName());
        self::assertSame(LightLevel::Direct, $reloaded->getLight());
    }

    public function testCannotEditPlantOfAnotherHousehold(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $this->createHousehold($user);
        $plant = $this->createPlant($this->createHousehold($this->createUser('x@example.com')), 'Theirs');
        $client->loginUser($user);

        $client->request('GET', '/plants/'.$plant->getId().'/edit');
        self::assertResponseStatusCodeSame(404);

        $client->request('GET', '/plants/'.$plant->getId());
        self::assertResponseStatusCodeSame(404);
    }

    public function testArchiveAndRestorePlant(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $household = $this->createHousehold($user);
        $plant = $this->createPlant($household, 'Fern');
        $client->loginUser($user);

        $client->request('GET', '/plants/'.$plant->getId());
        $client->submitForm('Gave it away');
        self::assertResponseRedirects('/plants');
        $client->followRedirect();
        self::assertSelectorTextContains('.empty', 'No plants yet');

        $client->request('GET', '/plants/archived');
        self::assertSelectorTextContains('.list', 'Fern');
        self::assertSelectorTextContains('.list', 'Gave it away');

        $client->request('GET', '/plants/'.$plant->getId());
        $client->submitForm('Restore');
        $client->followRedirect();
        self::assertSelectorNotExists('.notice');

        $repo = static::getContainer()->get(PlantRepository::class);
        self::assertCount(1, $repo->findActive($household));
        self::assertCount(0, $repo->findArchived($household));
    }

    public function testArchiveRequiresValidToken(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $plant = $this->createPlant($this->createHousehold($user), 'Fern');
        $client->loginUser($user);

        $client->request('POST', '/plants/'.$plant->getId().'/archive', ['_token' => 'forged']);

        self::assertResponseStatusCodeSame(403);
    }

    public function testFilterByRoom(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $household = $this->createHousehold($user);
        $kitchen = $this->createRoom($household, 'Kitchen');
        $bedroom = $this->createRoom($household, 'Bedroom');
        $this->createPlant($household, 'Basil', $kitchen);
        $this->createPlant($household, 'Sansa', $bedroom);
        $client->loginUser($user);

        $crawler = $client->request('GET', '/plants?room='.$kitchen->getId());

        self::assertSame(['Basil'], $crawler->filter('.plant-card h2')->each(fn ($n) => $n->text()));
        self::assertSelectorTextContains('.chip.is-active', 'Kitchen');
    }

    public function testUnknownRoomFilterShowsAllPlants(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $household = $this->createHousehold($user);
        $this->createPlant($household, 'Basil');
        $client->loginUser($user);

        $crawler = $client->request('GET', '/plants?room=999999');

        self::assertCount(1, $crawler->filter('.plant-card'));
    }

    public function testCardShowsNextTaskAndInitials(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $plant = $this->createPlant($this->createHousehold($user), 'Golden pothos');
        $this->em()->persist(new \App\Entity\CareSchedule($plant, \App\Enum\CareTaskType::Repot, 540, new DateTimeImmutable('2026-09-01')));
        $this->em()->persist(new \App\Entity\CareSchedule($plant, \App\Enum\CareTaskType::Water, 7, new DateTimeImmutable('2026-03-20')));
        $this->em()->flush();
        $client->loginUser($user);

        $client->request('GET', '/plants');

        self::assertSelectorTextContains('.plant-cover-empty', 'GP');
        self::assertSelectorTextContains('.next-task', 'Water 20 Mar');
    }

    public function testSearchByNameOrSpecies(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $household = $this->createHousehold($user);
        $species = new Species('Snake plant', 'Dracaena trifasciata', 14, LightLevel::Low);
        $this->em()->persist($species);
        $this->createPlant($household, 'Sansa')->setSpecies($species);
        $this->createPlant($household, 'Monty');
        $this->createPlant($household, '100% cotton');
        $this->em()->flush();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/plants?q=snake');
        self::assertSame(['Sansa'], $crawler->filter('.plant-card h2')->each(fn ($n) => $n->text()));

        $crawler = $client->request('GET', '/plants?q=MON');
        self::assertSame(['Monty'], $crawler->filter('.plant-card h2')->each(fn ($n) => $n->text()));

        $crawler = $client->request('GET', '/plants?q=%25');
        self::assertSame(['100% cotton'], $crawler->filter('.plant-card h2')->each(fn ($n) => $n->text()));

        $client->request('GET', '/plants?q=cactus');
        self::assertSelectorTextContains('.empty', 'No plants match');
    }

    public function testSpeciesOptionsCarryCareHints(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $this->createHousehold($user);
        $species = (new Species('Peace lily', 'Spathiphyllum wallisii', 5, LightLevel::Medium))->setToxicToPets(true);
        $this->em()->persist($species);
        $this->em()->flush();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/plants/new');
        $option = $crawler->filter('#plant_species option[value="'.$species->getId().'"]');

        self::assertSame('medium', $option->attr('data-light'));
        self::assertStringContainsString('Water about every 5 days, toxic to pets.', (string) $option->attr('data-hint'));
    }

    public function testPropagatePlant(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $household = $this->createHousehold($user);
        $plant = $this->createPlant($household, 'Pancake');
        $client->loginUser($user);

        $client->request('GET', '/plants/'.$plant->getId());
        $client->submitForm('Propagate');
        self::assertResponseRedirects();
        $client->followRedirect();

        self::assertSelectorTextContains('.flash', 'Welcome, Pancake (cutting)!');
        self::assertCount(2, static::getContainer()->get(PlantRepository::class)->findActive($household));
    }

    public function testToxicToPetsBadgeAndFilter(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $household = $this->createHousehold($user);
        $lily = (new Species('Peace lily', 'Spathiphyllum wallisii', 5, LightLevel::Medium))->setToxicToPets(true);
        $spider = new Species('Spider plant', 'Chlorophytum comosum', 6, LightLevel::Medium);
        $this->em()->persist($lily);
        $this->em()->persist($spider);
        $this->createPlant($household, 'Lily')->setSpecies($lily);
        $this->createPlant($household, 'Spidey')->setSpecies($spider);
        $this->createPlant($household, 'Unknown');
        $this->em()->flush();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/plants');
        self::assertSelectorCount(1, '.tag-warning');

        $crawler = $client->request('GET', '/plants?toxic=1');
        self::assertSame(['Lily'], $crawler->filter('.plant-card h2')->each(fn ($n) => $n->text()));
    }

    public function testPlantListQueryCountDoesNotGrowWithPlants(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $household = $this->createHousehold($user);
        $species = new Species('Monstera', 'Monstera deliciosa', 7, LightLevel::BrightIndirect);
        $this->em()->persist($species);
        for ($i = 1; $i <= 15; ++$i) {
            $plant = $this->createPlant($household, 'Plant '.$i, $this->createRoom($household, 'Room '.$i))->setSpecies($species);
            $this->em()->persist(new \App\Entity\CareSchedule($plant, \App\Enum\CareTaskType::Water, 7, new DateTimeImmutable('+'.$i.' days')));
        }
        $this->em()->flush();
        $client->loginUser($user);
        $client->disableReboot();
        $this->em()->clear();

        \App\Tests\Support\Doctrine\QueryCounter::reset();
        $client->request('GET', '/plants');

        self::assertResponseIsSuccessful();
        self::assertSelectorCount(15, '.plant-card');
        self::assertLessThanOrEqual(8, \count(\App\Tests\Support\Doctrine\QueryCounter::selects()), implode("\n", \App\Tests\Support\Doctrine\QueryCounter::selects()));
    }
}
