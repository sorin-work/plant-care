<?php

namespace App\Tests\Functional\Command;

use App\Care\DueTaskFinder;
use App\Demo\DemoSeeder;
use App\Repository\MembershipRepository;
use App\Repository\PlantRepository;
use App\Repository\UserRepository;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class SeedDemoCommandTest extends KernelTestCase
{
    private function runCommand(string $name): CommandTester
    {
        $tester = new CommandTester((new Application(self::$kernel ?? self::bootKernel()))->find($name));
        $tester->execute([]);

        return $tester;
    }

    public function testSeedsDemoAccount(): void
    {
        $this->runCommand('app:species:import');
        $tester = $this->runCommand('app:demo:seed');

        $tester->assertCommandIsSuccessful();
        $user = static::getContainer()->get(UserRepository::class)->findOneByEmail(DemoSeeder::EMAIL);
        self::assertNotNull($user);

        $household = static::getContainer()->get(MembershipRepository::class)->findForUser($user)[0]->getHousehold();
        $plants = static::getContainer()->get(PlantRepository::class)->findActive($household);
        self::assertCount(12, $plants);
        self::assertNotNull($plants[0]->getSpecies());

        $memberships = static::getContainer()->get(MembershipRepository::class)->findForUser($user);
        self::assertCount(2, $memberships);
        self::assertSame('Office', $memberships[1]->getHousehold()->getName());
        self::assertSame(\App\Enum\HouseholdRole::Editor, $memberships[1]->getRole());

        $due = static::getContainer()->get(DueTaskFinder::class)->dueOn($household, new DateTimeImmutable('today'));
        self::assertNotEmpty($due, 'the demo should have something to do today');
    }

    public function testSeedingTwiceIsANoop(): void
    {
        $this->runCommand('app:species:import');
        $this->runCommand('app:demo:seed');
        $tester = $this->runCommand('app:demo:seed');

        self::assertStringContainsString('already exists', $tester->getDisplay());
    }
}
