<?php

namespace App\Controller;

use App\Care\CareTasks;
use App\Entity\CareSchedule;
use App\Entity\User;
use App\Household\CurrentHousehold;
use DateTimeImmutable;
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
