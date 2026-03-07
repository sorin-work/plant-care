<?php

namespace App\Controller\Api;

use App\Care\CareTasks;
use App\Entity\CareSchedule;
use App\Entity\User;
use App\Household\HouseholdClock;
use App\Security\Permission;
use Symfony\Component\HttpFoundation\JsonResponse;
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
}
