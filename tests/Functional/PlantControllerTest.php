<?php

namespace App\Tests\Functional;

use App\Entity\Species;
use App\Enum\LightLevel;
use App\Repository\PlantRepository;
use App\Tests\Support\Factory;
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
}
