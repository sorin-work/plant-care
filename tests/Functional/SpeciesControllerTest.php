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
}
