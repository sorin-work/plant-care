<?php

namespace App\Controller;

use App\Entity\Species;
use App\Household\CurrentHousehold;
use App\Repository\SpeciesRepository;
use App\Security\Permission;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/species')]
final class SpeciesController extends AbstractController
{
    #[Route('', name: 'species_index', methods: ['GET'])]
    public function index(Request $request, SpeciesRepository $species, CurrentHousehold $current): Response
    {
        $query = $request->query->getString('q');

        return $this->render('species/index.html.twig', [
            'species' => $species->search($query, $current->get()),
            'query' => $query,
        ]);
    }

    #[Route('/{id}', name: 'species_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Species $species): Response
    {
        if (null !== $species->getHousehold() && !$this->isGranted(Permission::VIEW, $species->getHousehold())) {
            throw $this->createNotFoundException();
        }

        return $this->render('species/show.html.twig', ['species' => $species]);
    }
}
