<?php

namespace App\Tests\Functional;

use App\Entity\Species;
use App\Enum\LightLevel;
use App\Tests\Support\Factory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class SpeciesControllerTest extends WebTestCase
{
    use Factory;

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

    public function testAddCustomSpecies(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $household = $this->createHousehold($user);
        $client->loginUser($user);

        $client->request('GET', '/species/new');
        $client->submitForm('Add species', [
            'species[commonName]' => 'Rex begonia',
            'species[scientificName]' => 'Begonia rex',
            'species[waterIntervalDays]' => '5',
            'species[fertilizeIntervalDays]' => '',
            'species[repotIntervalMonths]' => '12',
            'species[light]' => 'medium',
        ]);
        self::assertResponseRedirects();
        $client->followRedirect();

        self::assertSelectorTextContains('h1', 'Rex begonia');
        self::assertSelectorTextContains('.facts', 'never');
        $species = $this->em()->getRepository(Species::class)->findOneBy(['scientificName' => 'Begonia rex']);
        self::assertSame($household->getId(), $species?->getHousehold()?->getId());
    }

    public function testDuplicateCustomSpeciesIsRejected(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $household = $this->createHousehold($user);
        $this->em()->persist((new Species('Rex', 'Begonia rex', 5, LightLevel::Medium))->setHousehold($household));
        $this->em()->flush();
        $client->loginUser($user);

        $client->request('GET', '/species/new');
        $client->submitForm('Add species', ['species[commonName]' => 'Rex again', 'species[scientificName]' => 'Begonia rex']);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('form', 'already added');
    }

    public function testViewerCannotAddSpecies(): void
    {
        $client = static::createClient();
        $household = $this->createHousehold($this->createUser('owner@example.com'));
        $viewer = $this->createUser('viewer@example.com');
        $this->addMember($household, $viewer, \App\Enum\HouseholdRole::Viewer);
        $client->loginUser($viewer);

        $client->request('GET', '/species/new');

        self::assertResponseStatusCodeSame(403);
    }

    public function testSpeciesPageListsYourPlants(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $household = $this->createHousehold($user);
        $species = new Species('Monstera', 'Monstera deliciosa', 7, LightLevel::BrightIndirect);
        $this->em()->persist($species);
        $this->createPlant($household, 'Monty')->setSpecies($species);
        $this->createPlant($this->createHousehold($this->createUser('x@example.com')), 'Not mine')->setSpecies($species);
        $this->em()->flush();
        $client->loginUser($user);

        $client->request('GET', '/species/'.$species->getId());

        self::assertSelectorTextContains('.species-plants', 'Monty');
        self::assertSelectorTextNotContains('.species-plants', 'Not mine');
    }
}
