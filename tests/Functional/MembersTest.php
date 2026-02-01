<?php

namespace App\Tests\Functional;

use App\Enum\HouseholdRole;
use App\Tests\Support\Factory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class MembersTest extends WebTestCase
{
    use Factory;

    public function testListsMembersWithRoles(): void
    {
        $client = static::createClient();
        $owner = $this->createUser('owner@example.com', 'Olga');
        $household = $this->createHousehold($owner, 'Flat');
        $this->addMember($household, $this->createUser('vic@example.com', 'Victor'), HouseholdRole::Viewer);
        $client->loginUser($owner);

        $crawler = $client->request('GET', '/household/members');

        self::assertResponseIsSuccessful();
        $rows = $crawler->filter('.members li')->each(fn ($li) => $li->text());
        self::assertCount(2, $rows);
        self::assertStringContainsString('Olga', $rows[0]);
        self::assertStringContainsString('Owner', $rows[0]);
        self::assertStringContainsString('Victor', $rows[1]);
        self::assertStringContainsString('Viewer', $rows[1]);
    }
}
