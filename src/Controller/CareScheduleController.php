<?php

namespace App\Controller;

use App\Entity\CareSchedule;
use App\Entity\Plant;
use App\Enum\CareTaskType;
use App\Form\CareScheduleType;
use App\Household\HouseholdClock;
use App\Security\Permission;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CareScheduleController extends AppController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    #[Route('/plants/{id}/schedules/{type}/new', name: 'schedule_new', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function new(Plant $plant, CareTaskType $type, Request $request, HouseholdClock $clock): Response
    {
        $this->guard(Permission::EDIT, $plant);
        foreach ($plant->getSchedules() as $existing) {
            if ($existing->getType() === $type) {
                return $this->redirectToRoute('schedule_edit', ['id' => $existing->getId()]);
            }
        }

        $schedule = new CareSchedule($plant, $type, match ($type) {
            CareTaskType::Water => 7,
            CareTaskType::Mist => 3,
            CareTaskType::Repot => 540,
            CareTaskType::Fertilize => 30,
        }, $clock->today($plant->getHousehold()));
        $plant->removeSchedule($schedule);

        return $this->handle($schedule, $request, 'schedule/new.html.twig');
    }

    #[Route('/schedules/{id}/edit', name: 'schedule_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(CareSchedule $schedule, Request $request): Response
    {
        $this->guard(Permission::EDIT, $schedule);

        return $this->handle($schedule, $request, 'schedule/edit.html.twig');
    }

    #[Route('/schedules/{id}/delete', name: 'schedule_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(CareSchedule $schedule, Request $request): Response
    {
        $plant = $schedule->getPlant();
        $this->guard(Permission::EDIT, $schedule);
        $this->assertCsrf('delete-schedule-'.$schedule->getId(), $request);

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
}
