<?php

namespace App\Controller;

use App\Entity\Species;
use App\Repository\SpeciesRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/species')]
final class SpeciesController extends AbstractController
{
    #[Route('', name: 'species_index', methods: ['GET'])]
    public function index(Request $request, SpeciesRepository $species): Response
    {
        $query = $request->query->getString('q');

        return $this->render('species/index.html.twig', [
            'species' => $species->search($query),
            'query' => $query,
        ]);
    }

    #[Route('/{id}', name: 'species_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Species $species): Response
    {
        return $this->render('species/show.html.twig', ['species' => $species]);
    }
}
