<?php

namespace App\Controller;

use App\Calendar\IcsWriter;
use App\Entity\Membership;
use App\Repository\CareScheduleRepository;
use App\Repository\MembershipRepository;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Read-only iCalendar feed of upcoming care, for phone and desktop calendars.
 * The URL contains a secret token instead of requiring a login.
 */
final class CalendarController extends AppController
{
    #[Route('/calendar/{token}.ics', name: 'calendar_feed', methods: ['GET'], requirements: ['token' => '[a-f0-9]{32}'])]
    public function feed(string $token, UserRepository $users, MembershipRepository $memberships, CareScheduleRepository $schedules): Response
    {
        $user = $users->findOneBy(['calendarToken' => $token]) ?? throw $this->createNotFoundException();
        $households = array_map(static fn (Membership $m) => $m->getHousehold(), $memberships->findForUser($user));

        $ics = new IcsWriter('Plant care');
        foreach ($schedules->findForHouseholds($households) as $schedule) {
            $plant = $schedule->getPlant();
            $due = max($schedule->getNextDueOn(), $schedule->getSnoozedUntil() ?? $schedule->getNextDueOn());
            $ics->addAllDayEvent(
                \sprintf('schedule-%d-%s@plant-care', $schedule->getId(), $due->format('Ymd')),
                $due,
                \sprintf('%s %s %s', $schedule->getType()->icon(), $schedule->getType()->label(), $plant->getName()),
                implode(' · ', array_filter([$plant->getRoom()?->getName(), $plant->getHousehold()->getName()])),
            );
        }

        return new Response($ics->render(), 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'inline; filename="plant-care.ics"',
            'Cache-Control' => 'private, max-age=900',
        ]);
    }
}
