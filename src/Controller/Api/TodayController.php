<?php

namespace App\Controller\Api;

use App\Care\DueTaskFinder;
use App\Household\HouseholdClock;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class TodayController extends ApiController
{
    #[Route('/api/today', name: 'api_today', methods: ['GET'])]
    public function __invoke(Request $request, DueTaskFinder $finder, HouseholdClock $clock): JsonResponse
    {
        $household = $this->household($request);
        $today = $clock->today($household);

        return $this->json([
            'household' => $household->getId(),
            'date' => $today->format('Y-m-d'),
            'tasks' => array_map(Presenter::task(...), $finder->dueOn($household, $today)),
        ]);
    }
}
