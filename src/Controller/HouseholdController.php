<?php

namespace App\Controller;

use App\Entity\Household;
use App\Enum\HouseholdRole;
use App\Form\AddMemberType;
use App\Form\HouseholdSettingsType;
use App\Household\CurrentHousehold;
use App\Household\MemberManager;
use App\Household\MembershipException;
use App\Repository\MembershipRepository;
use App\Security\Permission;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/household')]
final class HouseholdController extends AbstractController
{
    public function __construct(private readonly CurrentHousehold $current)
    {
    }

    #[Route('/settings', name: 'household_settings', methods: ['GET', 'POST'])]
    public function settings(Request $request, EntityManagerInterface $em): Response
    {
        $household = $this->current->get();
        $this->denyAccessUnlessGranted(Permission::MANAGE, $household, 'Only the owner can change household settings.');
        $form = $this->createForm(HouseholdSettingsType::class, $household);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Household settings saved.');

            return $this->redirectToRoute('household_settings');
        }

        return $this->render('household/settings.html.twig', [
            'form' => $form,
            'household' => $household,
        ], new Response(status: $form->isSubmitted() ? 422 : 200));
    }

    #[Route('/members', name: 'household_members', methods: ['GET', 'POST'])]
    public function members(Request $request, MembershipRepository $memberships, MemberManager $manager): Response
    {
        $household = $this->current->get();
        $form = $this->createForm(AddMemberType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $this->denyAccessUnlessGranted(Permission::MANAGE, $household);
        }
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var array{email: string, role: HouseholdRole} $data */
            $data = $form->getData();
            try {
                $membership = $manager->add($household, $data['email'], $data['role']);
                $this->addFlash('success', \sprintf('%s joined %s as %s.', $membership->getUser()->getDisplayName(), $household->getName(), mb_strtolower($membership->getRole()->label())));

                return $this->redirectToRoute('household_members');
            } catch (MembershipException $e) {
                $form->get('email')->addError(new FormError($e->getMessage()));
            }
        }

        return $this->render('household/members.html.twig', [
            'household' => $household,
            'memberships' => $memberships->findForHousehold($household),
            'form' => $form,
        ], new Response(status: $form->isSubmitted() ? 422 : 200));
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
