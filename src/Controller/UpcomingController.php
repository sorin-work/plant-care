<?php

namespace App\Controller;

use App\Care\UpcomingPlanner;
use App\Household\CurrentHousehold;
use App\Household\HouseholdClock;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class UpcomingController extends AppController
{
    #[Route('/upcoming', name: 'upcoming', methods: ['GET'])]
    public function __invoke(CurrentHousehold $current, UpcomingPlanner $planner, HouseholdClock $clock): Response
    {
        $household = $current->get();

        return $this->render('upcoming/index.html.twig', [
            'days' => $planner->nextDays($household, $clock->today($household)),
        ]);
    }
}
