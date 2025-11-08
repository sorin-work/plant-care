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
}
