<?php

namespace App\Controller;

use App\Care\DueTaskFinder;
use App\Household\CurrentHousehold;
use Psr\Clock\ClockInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'home', methods: ['GET'])]
    public function index(CurrentHousehold $current, DueTaskFinder $finder, ClockInterface $clock): Response
    {
        $today = $clock->now()->setTime(0, 0);

        return $this->render('today/index.html.twig', [
            'today' => $today,
            'tasks' => $finder->dueOn($current->get(), $today),
        ]);
    }
}
