<?php

namespace App\Tests\Functional;

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
}
