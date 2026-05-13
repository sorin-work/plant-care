<?php

namespace App\Tests\Functional\Command;

use App\Entity\CareSchedule;
use App\Enum\CareTaskType;
use App\Tests\Support\Factory;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class DueTasksCommandTest extends KernelTestCase
{
    use Factory;

    public function testListsDueTasksPerHousehold(): void
    {
        self::bootKernel();
        $user = $this->createUser();
        $household = $this->createHousehold($user, 'Flat');
        $this->em()->persist(new CareSchedule($this->createPlant($household, 'Monty', $this->createRoom($household, 'Hall')), CareTaskType::Water, 7, new DateTimeImmutable('-2 days')));
        $this->em()->persist(new CareSchedule($this->createPlant($household, 'Later'), CareTaskType::Water, 7, new DateTimeImmutable('+2 days')));
        $this->createHousehold($this->createUser('other@example.com'), 'Empty');
        $this->em()->flush();

        $tester = new CommandTester((new Application(self::$kernel))->find('app:tasks:due'));
        $tester->execute([]);

        $tester->assertCommandIsSuccessful();
        $display = $tester->getDisplay();
        self::assertStringContainsString('Flat', $display);
        self::assertMatchesRegularExpression('/Water\s+Monty\s+Hall\s+2 days late/', $display);
        self::assertStringNotContainsString('Later', $display);
        self::assertStringContainsString('Nothing due.', $display);
        self::assertStringContainsString('1 task due.', $display);
    }

    public function testFilterByHousehold(): void
    {
        self::bootKernel();
        $household = $this->createHousehold($this->createUser(), 'Flat');
        $this->createHousehold($this->createUser('other@example.com'), 'Other');

        $tester = new CommandTester((new Application(self::$kernel))->find('app:tasks:due'));
        $tester->execute(['--household' => (string) $household->getId()]);

        self::assertStringContainsString('Flat', $tester->getDisplay());
        self::assertStringNotContainsString('Other', $tester->getDisplay());
    }
}
