<?php

namespace App\Tests\Functional;

use App\Entity\Species;
use App\Enum\LightLevel;
use App\Tests\Support\Factory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class SpeciesControllerTest extends WebTestCase
{
    use Factory;

    protected function setUp(): void
    {
        parent::setUp();
        self::ensureKernelShutdown();
    }

    private function seed(): void
    {
        $this->em()->persist(new Species('Monstera', 'Monstera deliciosa', 7, LightLevel::BrightIndirect));
        $this->em()->persist(new Species('Snake plant', 'Dracaena trifasciata', 14, LightLevel::Low));
        $this->em()->flush();
    }

    public function testListsSpeciesAlphabetically(): void
    {
        $client = static::createClient();
        $this->seed();
        $client->loginUser($this->createUser());

        $crawler = $client->request('GET', '/species');

        self::assertResponseIsSuccessful();
        $names = $crawler->filter('tbody tr td:first-child a')->each(fn ($a) => $a->text());
        self::assertSame(['Monstera', 'Snake plant'], $names);
    }

    public function testSearchMatchesScientificName(): void
    {
        $client = static::createClient();
        $this->seed();
        $client->loginUser($this->createUser());

        $crawler = $client->request('GET', '/species?q=dracaena');

        self::assertCount(1, $crawler->filter('tbody tr'));
        self::assertSelectorTextContains('tbody', 'Snake plant');
    }

    public function testEmptySearchResult(): void
    {
        $client = static::createClient();
        $this->seed();
        $client->loginUser($this->createUser());

        $client->request('GET', '/species?q=cactus');

        self::assertSelectorTextContains('.empty', 'No species match');
    }

    public function testCustomSpeciesAreOnlyVisibleToTheirHousehold(): void
    {
        $client = static::createClient();
        $this->seed();
        $me = $this->createUser('me@example.com');
        $mine = $this->createHousehold($me);
        $other = $this->createHousehold($this->createUser('other@example.com'), 'Other');
        $ownSpecies = (new Species('Grandma\'s begonia', 'Begonia x', 5, LightLevel::Medium))->setHousehold($mine);
        $theirs = (new Species('Secret fern', 'Fernus secretus', 3, LightLevel::Low))->setHousehold($other);
        $this->em()->persist($ownSpecies);
        $this->em()->persist($theirs);
        $this->em()->flush();
        $client->loginUser($me);

        $crawler = $client->request('GET', '/species');
        $names = $crawler->filter('tbody tr td:first-child a')->each(fn ($a) => $a->text());
        self::assertContains("Grandma's begonia", $names);
        self::assertNotContains('Secret fern', $names);

        $client->request('GET', '/species/'.$theirs->getId());
        self::assertResponseStatusCodeSame(404);

        $crawler = $client->request('GET', '/plants/new');
        self::assertCount(0, $crawler->filter('#plant_species option[value="'.$theirs->getId().'"]'));
        self::assertCount(1, $crawler->filter('#plant_species option[value="'.$ownSpecies->getId().'"]'));
    }
}
