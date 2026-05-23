<?php

namespace App\Controller;

use App\Entity\Household;
use App\Entity\Membership;
use App\Entity\User;
use App\Enum\HouseholdRole;
use App\Form\AddMemberType;
use App\Form\HouseholdSettingsType;
use App\Form\NewHouseholdType;
use App\Household\CurrentHousehold;
use App\Household\HouseholdManager;
use App\Household\MemberManager;
use App\Household\MembershipException;
use App\Repository\MembershipRepository;
use App\Security\Permission;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/household')]
final class HouseholdController extends AppController
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

    #[Route('/new', name: 'household_new', methods: ['GET', 'POST'])]
    public function new(Request $request, HouseholdManager $households, EntityManagerInterface $em, #[CurrentUser] User $user): Response
    {
        $household = $households->create($user, '');
        $household->setTimezone($this->current->get()->getTimezone());
        $household->setHemisphere($this->current->get()->getHemisphere());
        $form = $this->createForm(NewHouseholdType::class, $household);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->current->switchTo($household);
            $this->addFlash('success', \sprintf('%s is ready. Add some rooms and plants!', $household->getName()));

            return $this->redirectToRoute('room_index');
        }

        return $this->render('household/new.html.twig', [
            'form' => $form,
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

    #[Route('/invitations', name: 'household_invite', methods: ['POST'])]
    public function invite(Request $request, MemberManager $manager, #[CurrentUser] User $user): Response
    {
        $household = $this->current->get();
        $this->denyAccessUnlessGranted(Permission::MANAGE, $household);
        $this->assertCsrf('invite', $request);

        $role = HouseholdRole::tryFrom($request->request->getString('role')) ?? HouseholdRole::Editor;
        [, $token] = $manager->invite($household, $role, $user);

        $this->addFlash('invite_link', $this->generateUrl('invitation_show', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL));

        return $this->redirectToRoute('household_members');
    }

    #[Route('/members/{id}/role', name: 'household_member_role', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function changeRole(Membership $membership, Request $request, MemberManager $manager): Response
    {
        $this->denyAccessUnlessGranted(Permission::MANAGE, $membership->getHousehold());
        $this->assertCsrf('member-'.$membership->getId(), $request);

        $role = HouseholdRole::tryFrom($request->request->getString('role'));
        if (null === $role) {
            throw new BadRequestHttpException('Unknown role.');
        }

        try {
            $manager->changeRole($membership, $role);
            $this->addFlash('success', \sprintf('%s is now %s.', $membership->getUser()->getDisplayName(), mb_strtolower($role->label())));
        } catch (MembershipException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('household_members');
    }

    #[Route('/members/{id}/remove', name: 'household_member_remove', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function removeMember(Membership $membership, Request $request, MemberManager $manager): Response
    {
        $this->denyAccessUnlessGranted(Permission::MANAGE, $membership->getHousehold());
        $this->assertCsrf('member-'.$membership->getId(), $request);

        try {
            $manager->remove($membership);
            $this->addFlash('success', \sprintf('%s was removed from the household.', $membership->getUser()->getDisplayName()));
        } catch (MembershipException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('household_members');
    }

    #[Route('/leave', name: 'household_leave', methods: ['POST'])]
    public function leave(Request $request, MemberManager $manager): Response
    {
        $membership = $this->current->membership();
        $this->assertCsrf('leave-household', $request);
        $name = $membership->getHousehold()->getName();

        $manager->leave($membership);
        $this->addFlash('success', \sprintf('You left %s.', $name));

        return $this->redirectToRoute('home');
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
