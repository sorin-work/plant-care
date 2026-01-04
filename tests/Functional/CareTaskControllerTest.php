<?php

namespace App\Tests\Functional;

use App\Entity\CareSchedule;
use App\Enum\CareAction;
use App\Enum\CareTaskType;
use App\Repository\CareEventRepository;
use App\Tests\Support\Factory;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class CareTaskControllerTest extends WebTestCase
{
    use Factory;

    public function testMarkWateringDone(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $plant = $this->createPlant($this->createHousehold($user), 'Monty');
        $schedule = new CareSchedule($plant, CareTaskType::Water, 7, new DateTimeImmutable('-2 days'));
        $schedule->setSeasonal(false);
        $this->em()->persist($schedule);
        $this->em()->flush();
        $client->loginUser($user);

        $client->request('GET', '/plants/'.$plant->getId());
        $client->submitForm('Done');
        self::assertResponseRedirects('/plants/'.$plant->getId());
        $client->followRedirect();

        self::assertSelectorTextContains('.flash', 'Monty: water done.');
        self::assertSelectorTextContains('.history', 'Water · done');
        $reloaded = $this->em()->find(CareSchedule::class, $schedule->getId());
        self::assertSame((new DateTimeImmutable('today +7 days'))->format('Y-m-d'), $reloaded?->getNextDueOn()->format('Y-m-d'));

        $events = static::getContainer()->get(CareEventRepository::class)->findRecentForPlant($plant);
        self::assertCount(1, $events);
        self::assertSame(CareAction::Done, $events[0]->getAction());
        self::assertSame($user->getId(), $events[0]->getUser()?->getId());
    }

    public function testCannotCompleteTaskOfAnotherHousehold(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $this->createHousehold($user);
        $foreign = $this->createPlant($this->createHousehold($this->createUser('x@example.com')), 'Theirs');
        $schedule = new CareSchedule($foreign, CareTaskType::Water, 7, new DateTimeImmutable('today'));
        $this->em()->persist($schedule);
        $this->em()->flush();
        $client->loginUser($user);

        $client->request('POST', '/tasks/'.$schedule->getId().'/done', ['_token' => 'x']);

        self::assertResponseStatusCodeSame(404);
    }

    public function testRedirectsBackToLocalPathOnly(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $plant = $this->createPlant($this->createHousehold($user), 'Monty');
        $schedule = new CareSchedule($plant, CareTaskType::Water, 7, new DateTimeImmutable('today'));
        $this->em()->persist($schedule);
        $this->em()->flush();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/plants/'.$plant->getId());
        $form = $crawler->selectButton('Done')->form();
        $client->request('POST', $form->getUri(), ['_token' => $form->get('_token')->getValue(), '_back' => '//evil.example.com/']);

        self::assertResponseRedirects('/plants/'.$plant->getId());
    }

    public function testSnoozeHidesTaskFromToday(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $plant = $this->createPlant($this->createHousehold($user), 'Monty');
        $this->em()->persist(new CareSchedule($plant, CareTaskType::Water, 7, new DateTimeImmutable('today')));
        $this->em()->flush();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/');
        $client->submit($crawler->selectButton('3 days')->form());
        self::assertResponseRedirects('/');
        $client->followRedirect();

        self::assertSelectorTextContains('.flash', 'Snoozed Monty for 3 days.');
        self::assertSelectorTextContains('.empty', 'All caught up');
    }

    public function testSnoozeWithInvalidDaysShowsError(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $plant = $this->createPlant($this->createHousehold($user), 'Monty');
        $this->em()->persist(new CareSchedule($plant, CareTaskType::Water, 7, new DateTimeImmutable('today')));
        $this->em()->flush();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/');
        $form = $crawler->selectButton('3 days')->form();
        $client->request('POST', $form->getUri(), ['_token' => $form->get('_token')->getValue(), 'days' => '-5', '_back' => '/']);
        $client->followRedirect();

        self::assertSelectorTextContains('.flash-error', '1 to 14 days');
        self::assertSelectorExists('.tasks');
    }

    public function testSkipLogsEventAndPlansNextOccurrence(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $plant = $this->createPlant($this->createHousehold($user), 'Monty');
        $schedule = (new CareSchedule($plant, CareTaskType::Fertilize, 30, new DateTimeImmutable('-2 days')))->setSeasonal(false);
        $this->em()->persist($schedule);
        $this->em()->flush();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/');
        $client->submit($crawler->selectButton('Skip this time')->form());
        $client->followRedirect();

        self::assertSelectorTextContains('.flash', 'Skipped fertilize for Monty this time.');
        $reloaded = $this->em()->find(CareSchedule::class, $schedule->getId());
        self::assertSame((new DateTimeImmutable('today +30 days'))->format('Y-m-d'), $reloaded?->getNextDueOn()->format('Y-m-d'));
        self::assertNull($reloaded->getLastDoneOn());

        $events = static::getContainer()->get(CareEventRepository::class)->findRecentForPlant($plant);
        self::assertSame(CareAction::Skipped, $events[0]->getAction());
    }
}
