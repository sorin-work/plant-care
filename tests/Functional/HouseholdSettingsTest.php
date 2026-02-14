<?php

namespace App\Tests\Functional;

use App\Enum\Hemisphere;
use App\Enum\HouseholdRole;
use App\Repository\HouseholdRepository;
use App\Tests\Support\Factory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class HouseholdSettingsTest extends WebTestCase
{
    use Factory;

    public function testOwnerCanUpdateSettings(): void
    {
        $client = static::createClient();
        $owner = $this->createUser();
        $household = $this->createHousehold($owner, 'Flat');
        $client->loginUser($owner);

        $client->request('GET', '/household/settings');
        self::assertResponseIsSuccessful();
        $client->submitForm('Save', [
            'household_settings[name]' => 'Sunny flat',
            'household_settings[timezone]' => 'Australia/Sydney',
            'household_settings[hemisphere]' => 'south',
        ]);
        self::assertResponseRedirects('/household/settings');

        $reloaded = static::getContainer()->get(HouseholdRepository::class)->find($household->getId());
        self::assertSame('Sunny flat', $reloaded?->getName());
        self::assertSame('Australia/Sydney', $reloaded->getTimezone());
        self::assertSame(Hemisphere::South, $reloaded->getHemisphere());
    }

    public function testBlankNameIsRejected(): void
    {
        $client = static::createClient();
        $owner = $this->createUser();
        $this->createHousehold($owner, 'Flat');
        $client->loginUser($owner);

        $client->request('GET', '/household/settings');
        $client->submitForm('Save', ['household_settings[name]' => '  ']);

        self::assertResponseStatusCodeSame(422);
    }

    public function testNonOwnerCannotOpenSettings(): void
    {
        $client = static::createClient();
        $owner = $this->createUser('owner@example.com', 'Owner');
        $household = $this->createHousehold($owner, 'Flat');
        $editor = $this->createUser('editor@example.com', 'Editor');
        $this->addMember($household, $editor, HouseholdRole::Editor);
        $client->loginUser($editor);

        $client->request('GET', '/household/settings');

        self::assertResponseStatusCodeSame(403);
    }

    public function testCreateAnotherHousehold(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $this->createHousehold($user, 'Flat');
        $client->loginUser($user);

        $client->request('GET', '/household/new');
        $client->submitForm('Create household', ['new_household[name]' => 'Office']);
        self::assertResponseRedirects('/rooms');
        $client->followRedirect();

        self::assertSelectorTextContains('.household-switch option[selected]', 'Office');
        $memberships = static::getContainer()->get(\App\Repository\MembershipRepository::class)->findForUser($user);
        self::assertCount(2, $memberships);
        self::assertSame(HouseholdRole::Owner, $memberships[1]->getRole());
    }

    public function testNewHouseholdNeedsAName(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $this->createHousehold($user, 'Flat');
        $client->loginUser($user);

        $client->request('GET', '/household/new');
        $client->submitForm('Create household', ['new_household[name]' => '']);

        self::assertResponseStatusCodeSame(422);
        self::assertCount(1, static::getContainer()->get(\App\Repository\MembershipRepository::class)->findForUser($user));
    }
}
