<?php

namespace App\Controller;

use App\Care\CareTasks;
use App\Entity\CareSchedule;
use App\Entity\User;
use App\Household\CurrentHousehold;
use DateTimeImmutable;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/tasks')]
final class CareTaskController extends AbstractController
{
    public function __construct(
        private readonly CareTasks $tasks,
        private readonly CurrentHousehold $current,
    ) {
    }

    #[Route('/{id}/done', name: 'task_done', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function done(CareSchedule $schedule, Request $request, #[CurrentUser] User $user): Response
    {
        $this->assertAllowed($schedule, $request);
        $this->tasks->markDone($schedule, $user, new DateTimeImmutable('today'));
        $this->addFlash('success', \sprintf('%s: %s done.', $schedule->getPlant()->getName(), mb_strtolower($schedule->getType()->label())));

        return $this->redirectBack($request, $schedule);
    }

    #[Route('/{id}/skip', name: 'task_skip', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function skip(CareSchedule $schedule, Request $request, #[CurrentUser] User $user): Response
    {
        $this->assertAllowed($schedule, $request);
        $this->tasks->skip($schedule, $user, new DateTimeImmutable('today'));
        $this->addFlash('success', \sprintf('Skipped %s for %s this time.', mb_strtolower($schedule->getType()->label()), $schedule->getPlant()->getName()));

        return $this->redirectBack($request, $schedule);
    }

    #[Route('/{id}/snooze', name: 'task_snooze', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function snooze(CareSchedule $schedule, Request $request, #[CurrentUser] User $user): Response
    {
        $this->assertAllowed($schedule, $request);
        $days = $request->request->getInt('days', 1);

        try {
            $this->tasks->snooze($schedule, $user, $days, new DateTimeImmutable('today'));
        } catch (InvalidArgumentException $e) {
            $this->addFlash('error', $e->getMessage());

            return $this->redirectBack($request, $schedule);
        }

        $this->addFlash('success', \sprintf('Snoozed %s for %d %s.', $schedule->getPlant()->getName(), $days, 1 === $days ? 'day' : 'days'));

        return $this->redirectBack($request, $schedule);
    }

    private function assertAllowed(CareSchedule $schedule, Request $request): void
    {
        if ($schedule->getPlant()->getHousehold()->getId() !== $this->current->get()->getId()) {
            throw $this->createNotFoundException();
        }
        if (!$this->isCsrfTokenValid('task-'.$schedule->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }
    }

    private function redirectBack(Request $request, CareSchedule $schedule): Response
    {
        $back = (string) $request->request->get('_back');
        if (str_starts_with($back, '/') && !str_starts_with($back, '//')) {
            return $this->redirect($back);
        }

        return $this->redirectToRoute('plant_show', ['id' => $schedule->getPlant()->getId()]);
    }
}
