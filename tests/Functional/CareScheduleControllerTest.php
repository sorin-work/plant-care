<?php

namespace App\Tests\Functional;

use App\Entity\CareSchedule;
use App\Enum\CareTaskType;
use App\Tests\Support\Factory;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class CareScheduleControllerTest extends WebTestCase
{
    use Factory;

    public function testEditSchedule(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $plant = $this->createPlant($this->createHousehold($user));
        $schedule = new CareSchedule($plant, CareTaskType::Water, 7, new DateTimeImmutable('today'));
        $this->em()->persist($schedule);
        $this->em()->flush();
        $client->loginUser($user);

        $client->request('GET', '/schedules/'.$schedule->getId().'/edit');
        $client->submitForm('Save', [
            'care_schedule[intervalDays]' => '10',
            'care_schedule[nextDueOn]' => '2026-05-01',
        ]);
        self::assertResponseRedirects('/plants/'.$plant->getId());

        $reloaded = $this->em()->find(CareSchedule::class, $schedule->getId());
        self::assertSame(10, $reloaded?->getIntervalDays());
        self::assertTrue($reloaded->isSeasonal());
        self::assertSame('2026-05-01', $reloaded->getNextDueOn()->format('Y-m-d'));
    }

    public function testIntervalMustBePositive(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $plant = $this->createPlant($this->createHousehold($user));
        $schedule = new CareSchedule($plant, CareTaskType::Water, 7, new DateTimeImmutable('today'));
        $this->em()->persist($schedule);
        $this->em()->flush();
        $client->loginUser($user);

        $client->request('GET', '/schedules/'.$schedule->getId().'/edit');
        $client->submitForm('Save', ['care_schedule[intervalDays]' => '0']);

        self::assertResponseStatusCodeSame(422);
    }

    public function testAddMissingSchedule(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $plant = $this->createPlant($this->createHousehold($user), 'Fern');
        $client->loginUser($user);

        $client->request('GET', '/plants/'.$plant->getId());
        $client->clickLink('+ Fertilize reminders');
        $client->submitForm('Save', ['care_schedule[intervalDays]' => '21']);
        $client->followRedirect();

        self::assertSelectorTextContains('.schedules', 'every 21 days');
        self::assertSelectorNotExists('a:contains("+ Fertilize reminders")');
    }

    public function testTurnOffReminders(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $plant = $this->createPlant($this->createHousehold($user));
        $schedule = new CareSchedule($plant, CareTaskType::Repot, 540, new DateTimeImmutable('today'));
        $this->em()->persist($schedule);
        $this->em()->flush();
        $client->loginUser($user);

        $client->request('GET', '/schedules/'.$schedule->getId().'/edit');
        $client->submitForm('Turn off these reminders');
        $client->followRedirect();

        self::assertSelectorTextContains('.schedules', 'No care schedules');
    }

    public function testPlantPageShowsSnoozeAndLastDone(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $plant = $this->createPlant($this->createHousehold($user));
        $schedule = (new CareSchedule($plant, CareTaskType::Water, 7, new DateTimeImmutable('2026-01-10')))
            ->setLastDoneOn(new DateTimeImmutable('2026-01-03'))
            ->setSnoozedUntil(new DateTimeImmutable('2026-01-12'));
        $this->em()->persist($schedule);
        $this->em()->flush();
        $client->loginUser($user);

        $client->request('GET', '/plants/'.$plant->getId());

        self::assertSelectorTextContains('.schedules', 'snoozed until 12 Jan');
        self::assertSelectorTextContains('.schedules', 'last done 3 Jan');
    }

    public function testAddMistingReminders(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $plant = $this->createPlant($this->createHousehold($user), 'Calathea');
        $client->loginUser($user);

        $client->request('GET', '/plants/'.$plant->getId());
        $client->clickLink('+ Mist reminders');
        self::assertInputValueSame('care_schedule[intervalDays]', '3');
        $client->submitForm('Save');
        $client->followRedirect();

        self::assertSelectorTextContains('.schedules', 'Mist');
    }

    public function testScheduleNotesShowOnToday(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $plant = $this->createPlant($this->createHousehold($user), 'Orchid');
        $schedule = new CareSchedule($plant, CareTaskType::Water, 7, new DateTimeImmutable('today'));
        $this->em()->persist($schedule);
        $this->em()->flush();
        $client->loginUser($user);

        $client->request('GET', '/schedules/'.$schedule->getId().'/edit');
        $client->submitForm('Save', ['care_schedule[notes]' => '  soak the bark for 10 minutes ']);
        $client->request('GET', '/');

        self::assertSelectorTextContains('.task-notes', 'soak the bark for 10 minutes');
    }
}
