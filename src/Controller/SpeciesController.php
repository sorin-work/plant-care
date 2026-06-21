<?php

namespace App\Controller;

use App\Entity\Species;
use App\Form\SpeciesType;
use App\Household\CurrentHousehold;
use App\Repository\PlantRepository;
use App\Repository\SpeciesRepository;
use App\Security\Permission;
use Doctrine\ORM\EntityManagerInterface;
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

    #[Route('/new', name: 'species_new', methods: ['GET', 'POST'])]
    public function new(Request $request, CurrentHousehold $current, EntityManagerInterface $em): Response
    {
        $household = $current->get();
        $this->denyAccessUnlessGranted(Permission::EDIT, $household);

        $form = $this->createForm(SpeciesType::class, null, ['household' => $household]);
        $form->handleRequest($request);
        /** @var Species|null $species */
        $species = $form->getData();

        if ($form->isSubmitted() && $form->isValid() && null !== $species) {
            $em->persist($species);
            $em->flush();
            $this->addFlash('success', \sprintf('%s was added to your species.', $species->getCommonName()));

            return $this->redirectToRoute('species_show', ['id' => $species->getId()]);
        }

        return $this->render('species/new.html.twig', ['form' => $form], new Response(status: $form->isSubmitted() ? 422 : 200));
    }

    #[Route('/{id}', name: 'species_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Species $species, PlantRepository $plants, CurrentHousehold $current): Response
    {
        if (null !== $species->getHousehold() && !$this->isGranted(Permission::VIEW, $species->getHousehold())) {
            throw $this->createNotFoundException();
        }

        return $this->render('species/show.html.twig', [
            'species' => $species,
            'plants' => $plants->findBy(['household' => $current->get(), 'species' => $species, 'archivedAt' => null], ['name' => 'ASC']),
        ]);
    }
}
