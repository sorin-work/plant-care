<?php

namespace App\Controller;

use App\Entity\CareSchedule;
use App\Entity\Plant;
use App\Enum\CareTaskType;
use App\Form\CareScheduleType;
use App\Household\CurrentHousehold;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CareScheduleController extends AbstractController
{
    public function __construct(
        private readonly CurrentHousehold $current,
        private readonly EntityManagerInterface $em,
    ) {
    }

    #[Route('/plants/{id}/schedules/{type}/new', name: 'schedule_new', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function new(Plant $plant, CareTaskType $type, Request $request): Response
    {
        $this->assertInCurrentHousehold($plant);
        foreach ($plant->getSchedules() as $existing) {
            if ($existing->getType() === $type) {
                return $this->redirectToRoute('schedule_edit', ['id' => $existing->getId()]);
            }
        }

        $schedule = new CareSchedule($plant, $type, CareTaskType::Water === $type ? 7 : 30, new DateTimeImmutable('today'));
        $plant->removeSchedule($schedule);

        return $this->handle($schedule, $request, 'schedule/new.html.twig');
    }

    #[Route('/schedules/{id}/edit', name: 'schedule_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(CareSchedule $schedule, Request $request): Response
    {
        $this->assertInCurrentHousehold($schedule->getPlant());

        return $this->handle($schedule, $request, 'schedule/edit.html.twig');
    }

    #[Route('/schedules/{id}/delete', name: 'schedule_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(CareSchedule $schedule, Request $request): Response
    {
        $plant = $schedule->getPlant();
        $this->assertInCurrentHousehold($plant);
        if (!$this->isCsrfTokenValid('delete-schedule-'.$schedule->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $plant->removeSchedule($schedule);
        $this->em->flush();
        $this->addFlash('success', \sprintf('%s reminders turned off for %s.', $schedule->getType()->label(), $plant->getName()));

        return $this->redirectToRoute('plant_show', ['id' => $plant->getId()]);
    }

    private function handle(CareSchedule $schedule, Request $request, string $template): Response
    {
        $form = $this->createForm(CareScheduleType::class, $schedule);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $schedule->getPlant()->addSchedule($schedule);
            $this->em->persist($schedule);
            $this->em->flush();
            $this->addFlash('success', 'Schedule saved.');

            return $this->redirectToRoute('plant_show', ['id' => $schedule->getPlant()->getId()]);
        }

        return $this->render($template, [
            'schedule' => $schedule,
            'plant' => $schedule->getPlant(),
            'form' => $form,
        ], new Response(status: $form->isSubmitted() ? 422 : 200));
    }

    private function assertInCurrentHousehold(Plant $plant): void
    {
        if ($plant->getHousehold()->getId() !== $this->current->get()->getId()) {
            throw $this->createNotFoundException();
        }
    }
}
