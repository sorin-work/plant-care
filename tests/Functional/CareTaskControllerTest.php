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

    public function testJsonResponseForBackgroundRequests(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $plant = $this->createPlant($this->createHousehold($user), 'Monty');
        $this->em()->persist((new CareSchedule($plant, CareTaskType::Water, 7, new DateTimeImmutable('today')))->setSeasonal(false));
        $this->em()->flush();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/');
        $form = $crawler->selectButton('Done')->form();
        $client->request('POST', $form->getUri(), ['_token' => $form->get('_token')->getValue()], [], ['HTTP_ACCEPT' => 'application/json']);

        self::assertResponseIsSuccessful();
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertTrue($data['ok']);
        self::assertSame('Monty: water done.', $data['message']);
        self::assertSame((new DateTimeImmutable('today +7 days'))->format('Y-m-d'), $data['nextDueOn']);
    }

    public function testJsonErrorForInvalidSnooze(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $plant = $this->createPlant($this->createHousehold($user), 'Monty');
        $this->em()->persist(new CareSchedule($plant, CareTaskType::Water, 7, new DateTimeImmutable('today')));
        $this->em()->flush();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/');
        $form = $crawler->selectButton('1 day')->form();
        $client->request('POST', $form->getUri(), ['_token' => $form->get('_token')->getValue(), 'days' => '30'], [], ['HTTP_ACCEPT' => 'application/json']);

        self::assertResponseStatusCodeSame(422);
        self::assertFalse(json_decode((string) $client->getResponse()->getContent(), true)['ok']);
    }

    public function testWaterAllInRoom(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $household = $this->createHousehold($user);
        $kitchen = $this->createRoom($household, 'Kitchen');
        foreach (['Basil', 'Thyme', 'Mint'] as $name) {
            $this->em()->persist(new CareSchedule($this->createPlant($household, $name, $kitchen), CareTaskType::Water, 2, new DateTimeImmutable('today')));
        }
        $fertilize = new CareSchedule($this->createPlant($household, 'Chili', $kitchen), CareTaskType::Fertilize, 14, new DateTimeImmutable('today'));
        $this->em()->persist($fertilize);
        $this->em()->flush();
        $client->loginUser($user);

        $client->request('GET', '/');
        $client->submitForm('💧 Water all 3');
        $client->followRedirect();

        self::assertSelectorTextContains('.flash', '3 tasks done.');
        self::assertSelectorTextContains('.tasks', 'Chili');
        self::assertSelectorCount(1, '.tasks li');
    }

    public function testDoneManyChecksEachTask(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $household = $this->createHousehold($user);
        foreach (['A', 'B'] as $name) {
            $this->em()->persist(new CareSchedule($this->createPlant($household, $name), CareTaskType::Water, 2, new DateTimeImmutable('today')));
        }
        $foreign = new CareSchedule($this->createPlant($this->createHousehold($this->createUser('x@example.com'))), CareTaskType::Water, 2, new DateTimeImmutable('today'));
        $this->em()->persist($foreign);
        $this->em()->flush();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/');
        $token = $crawler->filter('.room-group-header input[name=_token]')->attr('value');
        $client->request('POST', '/tasks/done', ['_token' => $token, 'ids' => [$foreign->getId()]]);

        self::assertResponseStatusCodeSame(404);
    }

    public function testDoneYesterday(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $plant = $this->createPlant($this->createHousehold($user), 'Monty');
        $schedule = (new CareSchedule($plant, CareTaskType::Water, 7, new DateTimeImmutable('-1 day')))->setSeasonal(false);
        $this->em()->persist($schedule);
        $this->em()->flush();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/');
        $client->submit($crawler->selectButton('Did it yesterday')->form());
        $client->followRedirect();

        self::assertSelectorTextContains('.flash', 'Monty: water done yesterday.');
        $reloaded = $this->em()->find(CareSchedule::class, $schedule->getId());
        self::assertSame((new DateTimeImmutable('yesterday'))->format('Y-m-d'), $reloaded?->getLastDoneOn()?->format('Y-m-d'));
        self::assertSame((new DateTimeImmutable('yesterday +7 days'))->format('Y-m-d'), $reloaded->getNextDueOn()->format('Y-m-d'));
    }

    public function testBackdatingIsLimitedToAWeek(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $plant = $this->createPlant($this->createHousehold($user), 'Monty');
        $schedule = (new CareSchedule($plant, CareTaskType::Water, 7, new DateTimeImmutable('today')))->setSeasonal(false);
        $this->em()->persist($schedule);
        $this->em()->flush();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/');
        $form = $crawler->selectButton('Done')->form();
        $client->request('POST', $form->getUri(), ['_token' => $form->get('_token')->getValue(), 'days_ago' => '400']);

        $reloaded = $this->em()->find(CareSchedule::class, $schedule->getId());
        self::assertSame((new DateTimeImmutable('-7 days'))->format('Y-m-d'), $reloaded?->getLastDoneOn()?->format('Y-m-d'));
    }
}
