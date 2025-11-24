<?php

namespace App\Controller;

use App\Entity\Plant;
use App\Form\PlantType;
use App\Household\CurrentHousehold;
use App\Repository\PlantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/plants')]
final class PlantController extends AbstractController
{
    public function __construct(
        private readonly CurrentHousehold $current,
        private readonly EntityManagerInterface $em,
    ) {
    }

    #[Route('', name: 'plant_index', methods: ['GET'])]
    public function index(PlantRepository $plants): Response
    {
        return $this->render('plant/index.html.twig', [
            'plants' => $plants->findActive($this->current->get()),
        ]);
    }

    #[Route('/new', name: 'plant_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $household = $this->current->get();
        $plant = new Plant($household);
        $form = $this->createForm(PlantType::class, $plant, ['household' => $household]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($plant);
            $this->em->flush();
            $this->addFlash('success', \sprintf('%s has been added.', $plant->getName()));

            return $this->redirectToRoute('plant_show', ['id' => $plant->getId()]);
        }

        return $this->render('plant/new.html.twig', [
            'form' => $form,
        ], new Response(status: $form->isSubmitted() ? 422 : 200));
    }

    #[Route('/{id}', name: 'plant_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Plant $plant): Response
    {
        $this->assertInCurrentHousehold($plant);

        return $this->render('plant/show.html.twig', ['plant' => $plant]);
    }

    #[Route('/{id}/edit', name: 'plant_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Plant $plant, Request $request): Response
    {
        $this->assertInCurrentHousehold($plant);
        $form = $this->createForm(PlantType::class, $plant, ['household' => $plant->getHousehold()]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();
            $this->addFlash('success', 'Changes saved.');

            return $this->redirectToRoute('plant_show', ['id' => $plant->getId()]);
        }

        return $this->render('plant/edit.html.twig', [
            'plant' => $plant,
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
