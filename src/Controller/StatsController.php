<?php

namespace App\Controller;

use App\Household\CurrentHousehold;
use App\Household\HouseholdClock;
use App\Stats\CareStats;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class StatsController extends AppController
{
    #[Route('/stats', name: 'stats', methods: ['GET'])]
    public function __invoke(CurrentHousehold $current, CareStats $stats, HouseholdClock $clock): Response
    {
        $household = $current->get();
        $data = $stats->forHousehold($household, $clock->today($household));
        $max = max(1, ...array_column($data['weeks'], 'done'));

        return $this->render('stats/index.html.twig', $data + ['max' => $max]);
    }
}
