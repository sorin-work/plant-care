<?php

namespace App\Tests\Functional;

use App\Entity\Household;
use App\Entity\User;
use App\Enum\HouseholdRole;
use App\Tests\Support\Factory;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * What each household role can and cannot do.
 */
final class PermissionsTest extends WebTestCase
{
    use Factory;

    /**
     * @return array{KernelBrowser, Household, User}
     */
    private function memberClient(HouseholdRole $role): array
    {
        $client = static::createClient();
        $owner = $this->createUser('owner@example.com', 'Owner');
        $household = $this->createHousehold($owner, 'Shared flat');
        $member = $this->createUser('member@example.com', 'Member');
        $this->addMember($household, $member, $role);
        $client->loginUser($member);

        return [$client, $household, $member];
    }

    public function testViewerCanSeePlantsButNotEditThem(): void
    {
        [$client, $household] = $this->memberClient(HouseholdRole::Viewer);
        $plant = $this->createPlant($household, 'Monty');

        $client->request('GET', '/plants/'.$plant->getId());
        self::assertResponseIsSuccessful();

        $client->request('GET', '/plants/'.$plant->getId().'/edit');
        self::assertResponseStatusCodeSame(403);

        $client->request('GET', '/plants/new');
        self::assertResponseStatusCodeSame(403);
    }

    public function testViewerSeesRoomsWithoutForm(): void
    {
        [$client, $household] = $this->memberClient(HouseholdRole::Viewer);
        $this->createRoom($household, 'Kitchen');

        $client->request('GET', '/rooms');

        self::assertSelectorTextContains('.list', 'Kitchen');
        self::assertSelectorNotExists('button:contains("Add room")');
        self::assertSelectorNotExists('.list .actions');
    }

    public function testEditorCanEditPlants(): void
    {
        [$client, $household] = $this->memberClient(HouseholdRole::Editor);
        $plant = $this->createPlant($household, 'Monty');

        $client->request('GET', '/plants/'.$plant->getId().'/edit');

        self::assertResponseIsSuccessful();
    }

    public function testViewerCannotCompleteTasks(): void
    {
        [$client, $household] = $this->memberClient(HouseholdRole::Viewer);
        $plant = $this->createPlant($household, 'Monty');
        $schedule = new \App\Entity\CareSchedule($plant, \App\Enum\CareTaskType::Water, 7, new DateTimeImmutable('today'));
        $this->em()->persist($schedule);
        $this->em()->flush();

        $client->request('POST', '/tasks/'.$schedule->getId().'/done', ['_token' => 'irrelevant']);
        self::assertResponseStatusCodeSame(403);

        $client->request('GET', '/schedules/'.$schedule->getId().'/edit');
        self::assertResponseStatusCodeSame(403);
    }

    public function testViewerCannotWriteJournal(): void
    {
        [$client, $household] = $this->memberClient(HouseholdRole::Viewer);
        $plant = $this->createPlant($household, 'Monty');

        $client->request('GET', '/plants/'.$plant->getId().'/journal/new');
        self::assertResponseStatusCodeSame(403);

        $client->request('GET', '/plants/'.$plant->getId().'/journal');
        self::assertResponseIsSuccessful();
    }

    public function testEditorCannotChangeSettings(): void
    {
        [$client] = $this->memberClient(HouseholdRole::Editor);

        $client->request('GET', '/household/settings');

        self::assertResponseStatusCodeSame(403);
    }
}
