<?php

namespace App\Controller;

use App\Household\CurrentHousehold;
use App\Repository\PlantRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/plants')]
final class PlantController extends AbstractController
{
    public function __construct(private readonly CurrentHousehold $current)
    {
    }

    #[Route('', name: 'plant_index', methods: ['GET'])]
    public function index(PlantRepository $plants): Response
    {
        return $this->render('plant/index.html.twig', [
            'plants' => $plants->findActive($this->current->get()),
        ]);
    }
}
