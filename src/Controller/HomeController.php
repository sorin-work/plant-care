<?php

namespace App\Controller;

use App\Care\DueTaskFinder;
use App\Care\TaskGrouper;
use App\Household\CurrentHousehold;
use App\Household\HouseholdClock;
use App\Repository\PlantRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'home', methods: ['GET'])]
    public function index(CurrentHousehold $current, DueTaskFinder $finder, TaskGrouper $grouper, HouseholdClock $clock, PlantRepository $plants): Response
    {
        $household = $current->get();
        $today = $clock->today($household);
        $tasks = $finder->dueOn($household, $today);

        return $this->render('today/index.html.twig', [
            'today' => $today,
            'count' => \count($tasks),
            'groups' => $grouper->byRoom($tasks),
            'has_plants' => 0 === \count($tasks) ? [] !== $plants->findActive($household) : true,
        ]);
    }
}
