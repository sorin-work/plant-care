<?php

namespace App\Tests\Functional\Command;

use App\Entity\CareSchedule;
use App\Enum\CareTaskType;
use App\Tests\Support\Factory;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\FrameworkBundle\Test\MailerAssertionsTrait;
use Symfony\Component\Console\Tester\CommandTester;

final class SendDigestCommandTest extends KernelTestCase
{
    use Factory;
    use MailerAssertionsTrait;

    private function send(): CommandTester
    {
        $tester = new CommandTester((new Application(self::$kernel))->find('app:digest:send'));
        $tester->execute([]);

        return $tester;
    }

    public function testSendsDigestToOptedInUsersWithDueTasks(): void
    {
        self::bootKernel();
        $ana = $this->createUser('ana@example.com', 'Ana')->setDigestEnabled(true);
        $household = $this->createHousehold($ana, 'Flat');
        $this->em()->persist(new CareSchedule($this->createPlant($household, 'Monty'), CareTaskType::Water, 7, new DateTimeImmutable('-1 day')));
        $bob = $this->createUser('bob@example.com', 'Bob');
        $this->addMember($household, $bob, \App\Enum\HouseholdRole::Editor);
        $this->em()->flush();

        $tester = $this->send();

        self::assertStringContainsString('Sent 1 digest email.', $tester->getDisplay());
        self::assertEmailCount(1);
        $email = self::getMailerMessage();
        self::assertNotNull($email);
        self::assertEmailAddressContains($email, 'To', 'ana@example.com');
        self::assertEmailHeaderSame($email, 'Subject', '1 thing for your plants today');
        self::assertEmailTextBodyContains($email, '- Water Monty, 1 days late');
        self::assertEmailHtmlBodyContains($email, 'Good morning, Ana!');
    }

    public function testNothingDueSendsNothing(): void
    {
        self::bootKernel();
        $ana = $this->createUser('ana@example.com', 'Ana')->setDigestEnabled(true);
        $household = $this->createHousehold($ana, 'Flat');
        $this->em()->persist(new CareSchedule($this->createPlant($household, 'Monty'), CareTaskType::Water, 7, new DateTimeImmutable('+3 days')));
        $this->em()->flush();

        $this->send();

        self::assertEmailCount(0);
    }
}
