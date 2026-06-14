<?php

namespace App\Tests\Functional\Api;

use App\Entity\JournalEntry;
use App\Tests\Support\Factory;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class JournalTest extends WebTestCase
{
    use Factory;

    public function testListsJournalNewestFirst(): void
    {
        $client = static::createClient();
        $user = $this->createUser(name: 'Mara');
        $plant = $this->createPlant($this->createHousehold($user), 'Monty');
        $this->em()->persist((new JournalEntry($plant, $user, new DateTimeImmutable('2026-06-01')))->setNote('Older'));
        $this->em()->persist((new JournalEntry($plant, null, new DateTimeImmutable('2026-06-10')))->setNote('Newer'));
        $this->em()->flush();

        $client->request('GET', '/api/plants/'.$plant->getId().'/journal', server: $this->apiHeaders($user));

        self::assertResponseIsSuccessful();
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertSame(1, $data['pages']);
        self::assertSame(2, $data['total']);
        self::assertSame(['Newer', 'Older'], array_column($data['entries'], 'note'));
        self::assertSame('Mara', $data['entries'][1]['author']);
        self::assertNull($data['entries'][0]['photo']);
    }

    public function testForeignJournalIsNotFound(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $this->createHousehold($user);
        $plant = $this->createPlant($this->createHousehold($this->createUser('x@example.com')));

        $client->request('GET', '/api/plants/'.$plant->getId().'/journal', server: $this->apiHeaders($user));

        self::assertResponseStatusCodeSame(404);
    }

    public function testAddEntry(): void
    {
        $client = static::createClient();
        $user = $this->createUser(name: 'Mara');
        $plant = $this->createPlant($this->createHousehold($user), 'Monty');

        $client->request('POST', '/api/plants/'.$plant->getId().'/journal', server: $this->apiHeaders($user) + ['CONTENT_TYPE' => 'application/json'], content: '{"note": "Sent from my watering can", "date": "2026-06-13"}');

        self::assertResponseStatusCodeSame(201);
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertSame('2026-06-13', $data['date']);
        self::assertSame('Mara', $data['author']);
        self::assertCount(1, $this->em()->getRepository(JournalEntry::class)->findAll());
    }

    public function testAddEntryValidation(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $plant = $this->createPlant($this->createHousehold($user));
        $headers = $this->apiHeaders($user) + ['CONTENT_TYPE' => 'application/json'];

        $client->request('POST', '/api/plants/'.$plant->getId().'/journal', server: $headers, content: '{"note": ""}');
        self::assertResponseStatusCodeSame(422);

        $client->request('POST', '/api/plants/'.$plant->getId().'/journal', server: $headers, content: '{"note": "x", "date": "13.06.2026"}');
        self::assertResponseStatusCodeSame(422);

        $client->request('POST', '/api/plants/'.$plant->getId().'/journal', server: $headers, content: '{"note": "x", "date": "2099-01-01"}');
        self::assertResponseStatusCodeSame(422);
        self::assertStringContainsString('future', (string) $client->getResponse()->getContent());
    }

    public function testViewerCannotAddEntry(): void
    {
        $client = static::createClient();
        $owner = $this->createUser('owner@example.com');
        $household = $this->createHousehold($owner);
        $viewer = $this->createUser('viewer@example.com');
        $this->addMember($household, $viewer, \App\Enum\HouseholdRole::Viewer);
        $plant = $this->createPlant($household);

        $client->request('POST', '/api/plants/'.$plant->getId().'/journal', server: $this->apiHeaders($viewer) + ['CONTENT_TYPE' => 'application/json'], content: '{"note": "hi"}');

        self::assertResponseStatusCodeSame(403);
    }
}
