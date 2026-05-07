<?php

namespace App\Tests\Functional;

use App\Entity\CareSchedule;
use App\Enum\CareTaskType;
use App\Tests\Support\Factory;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class CalendarTest extends WebTestCase
{
    use Factory;

    public function testFeedListsNextCareForAllHouseholds(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $household = $this->createHousehold($user, 'Flat');
        $plant = $this->createPlant($household, 'Monty', $this->createRoom($household, 'Living room'));
        $this->em()->persist(new CareSchedule($plant, CareTaskType::Water, 7, new DateTimeImmutable('2026-03-20')));
        $token = $user->resetCalendarToken();
        $this->em()->flush();

        $client->request('GET', '/calendar/'.$token.'.ics');

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('content-type', 'text/calendar; charset=utf-8');
        $body = (string) $client->getResponse()->getContent();
        self::assertStringContainsString('DTSTART;VALUE=DATE:20260320', $body);
        self::assertStringContainsString('Water Monty', $body);
        self::assertStringContainsString('DESCRIPTION:Living room · Flat', $body);
    }

    public function testUnknownTokenIs404(): void
    {
        $client = static::createClient();
        $client->request('GET', '/calendar/'.str_repeat('0', 32).'.ics');

        self::assertResponseStatusCodeSame(404);
    }

    public function testEventKeepsItsUidWhenTheDateMoves(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $schedule = new CareSchedule($this->createPlant($this->createHousehold($user), 'Monty'), CareTaskType::Water, 7, new DateTimeImmutable('2026-05-07'));
        $this->em()->persist($schedule);
        $token = $user->resetCalendarToken();
        $this->em()->flush();

        $client->request('GET', '/calendar/'.$token.'.ics');
        $first = (string) $client->getResponse()->getContent();

        $schedule->setNextDueOn(new DateTimeImmutable('2026-05-14'));
        $this->em()->flush();
        $client->request('GET', '/calendar/'.$token.'.ics');
        $second = (string) $client->getResponse()->getContent();

        self::assertStringContainsString('UID:schedule-'.$schedule->getId().'@plant-care', $first);
        self::assertStringContainsString('UID:schedule-'.$schedule->getId().'@plant-care', $second);
        self::assertStringContainsString('DTSTART;VALUE=DATE:20260514', $second);
    }
}
