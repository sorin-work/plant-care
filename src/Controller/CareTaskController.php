<?php

namespace App\Controller;

use App\Care\CareTasks;
use App\Entity\CareSchedule;
use App\Entity\User;
use App\Household\HouseholdClock;
use App\Repository\CareScheduleRepository;
use App\Security\Permission;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

/**
 * Done / skip / snooze actions. Plain form posts redirect back with a flash
 * message; requests that accept JSON (the task Stimulus controller) get a
 * small JSON body instead.
 */
#[Route('/tasks')]
final class CareTaskController extends AppController
{
    public function __construct(
        private readonly CareTasks $tasks,
        private readonly HouseholdClock $clock,
    ) {
    }

    #[Route('/{id}/done', name: 'task_done', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function done(CareSchedule $schedule, Request $request, #[CurrentUser] User $user): Response
    {
        $this->assertAllowed($schedule, $request);
        $this->tasks->markDone($schedule, $user, $this->clock->today($schedule->getPlant()->getHousehold()));

        return $this->respond($request, $schedule, \sprintf('%s: %s done.', $schedule->getPlant()->getName(), mb_strtolower($schedule->getType()->label())));
    }

    /**
     * Marks several tasks done at once, e.g. watering everything in a room.
     */
    #[Route('/done', name: 'task_done_many', methods: ['POST'])]
    public function doneMany(Request $request, CareScheduleRepository $schedules, #[CurrentUser] User $user): Response
    {
        $this->assertCsrf('tasks-many', $request);
        $ids = array_map('intval', $request->request->all('ids'));

        $count = 0;
        foreach ($ids === [] ? [] : $schedules->findBy(['id' => $ids]) as $schedule) {
            $this->guard(Permission::CARE, $schedule);
            $this->tasks->markDone($schedule, $user, $this->clock->today($schedule->getPlant()->getHousehold()));
            ++$count;
        }

        $this->addFlash('success', \sprintf('%d %s done.', $count, 1 === $count ? 'task' : 'tasks'));

        return $this->redirectToRoute('home');
    }

    #[Route('/{id}/skip', name: 'task_skip', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function skip(CareSchedule $schedule, Request $request, #[CurrentUser] User $user): Response
    {
        $this->assertAllowed($schedule, $request);
        $this->tasks->skip($schedule, $user, $this->clock->today($schedule->getPlant()->getHousehold()));

        return $this->respond($request, $schedule, \sprintf('Skipped %s for %s this time.', mb_strtolower($schedule->getType()->label()), $schedule->getPlant()->getName()));
    }

    #[Route('/{id}/snooze', name: 'task_snooze', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function snooze(CareSchedule $schedule, Request $request, #[CurrentUser] User $user): Response
    {
        $this->assertAllowed($schedule, $request);
        $days = $request->request->getInt('days', 1);

        try {
            $this->tasks->snooze($schedule, $user, $days, $this->clock->today($schedule->getPlant()->getHousehold()));
        } catch (InvalidArgumentException $e) {
            return $this->respond($request, $schedule, $e->getMessage(), false);
        }

        return $this->respond($request, $schedule, \sprintf('Snoozed %s for %d %s.', $schedule->getPlant()->getName(), $days, 1 === $days ? 'day' : 'days'));
    }

    private function assertAllowed(CareSchedule $schedule, Request $request): void
    {
        $this->guard(Permission::CARE, $schedule);
        $this->assertCsrf('task-'.$schedule->getId(), $request);
    }

    private function respond(Request $request, CareSchedule $schedule, string $message, bool $ok = true): Response
    {
        if (\in_array('application/json', $request->getAcceptableContentTypes(), true)) {
            return new JsonResponse([
                'ok' => $ok,
                'message' => $message,
                'nextDueOn' => $schedule->getNextDueOn()->format('Y-m-d'),
                'snoozedUntil' => $schedule->getSnoozedUntil()?->format('Y-m-d'),
            ], $ok ? Response::HTTP_OK : Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->addFlash($ok ? 'success' : 'error', $message);

        $back = (string) $request->request->get('_back');
        if (str_starts_with($back, '/') && !str_starts_with($back, '//')) {
            return $this->redirect($back);
        }

        return $this->redirectToRoute('plant_show', ['id' => $schedule->getPlant()->getId()]);
    }
}
