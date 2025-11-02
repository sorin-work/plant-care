<?php

namespace App\Controller;

use App\Entity\Household;
use App\Household\CurrentHousehold;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/household')]
final class HouseholdController extends AbstractController
{
    public function __construct(private readonly CurrentHousehold $current)
    {
    }

    #[Route('/{id}/switch', name: 'household_switch', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function switch(Household $household, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('switch-household', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        if (!$this->current->switchTo($household)) {
            throw $this->createNotFoundException();
        }

        $this->addFlash('success', \sprintf('Switched to %s.', $household->getName()));

        return $this->redirectToRoute('home');
    }
}
