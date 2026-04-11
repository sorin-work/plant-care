<?php

namespace App\Controller\Api;

use App\Care\CareTasks;
use App\Entity\CareSchedule;
use App\Entity\User;
use App\Household\HouseholdClock;
use App\Security\Permission;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/tasks/{id}', requirements: ['id' => '\d+'])]
final class TaskController extends ApiController
{
    #[Route('/done', name: 'api_task_done', methods: ['POST'])]
    public function done(CareSchedule $schedule, CareTasks $tasks, HouseholdClock $clock, #[CurrentUser] User $user): JsonResponse
    {
        $this->guard(Permission::CARE, $schedule);
        $tasks->markDone($schedule, $user, $clock->today($schedule->getPlant()->getHousehold()));

        return $this->json(Presenter::schedule($schedule));
    }

    #[Route('/skip', name: 'api_task_skip', methods: ['POST'])]
    public function skip(CareSchedule $schedule, CareTasks $tasks, HouseholdClock $clock, #[CurrentUser] User $user): JsonResponse
    {
        $this->guard(Permission::CARE, $schedule);
        $tasks->skip($schedule, $user, $clock->today($schedule->getPlant()->getHousehold()));

        return $this->json(Presenter::schedule($schedule));
    }

    #[Route('/snooze', name: 'api_task_snooze', methods: ['POST'])]
    public function snooze(CareSchedule $schedule, Request $request, CareTasks $tasks, HouseholdClock $clock, #[CurrentUser] User $user): JsonResponse
    {
        $this->guard(Permission::CARE, $schedule);
        $days = $request->toArray()['days'] ?? 1;
        if (!\is_int($days)) {
            return $this->json(['error' => '"days" must be a whole number.'], 422);
        }

        try {
            $tasks->snooze($schedule, $user, $days, $clock->today($schedule->getPlant()->getHousehold()));
        } catch (InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], 422);
        }

        return $this->json(Presenter::schedule($schedule));
    }
}
