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
}
