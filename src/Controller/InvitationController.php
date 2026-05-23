<?php

namespace App\Controller;

use App\Entity\User;
use App\Household\CurrentHousehold;
use App\Household\MemberManager;
use App\Household\MembershipException;
use App\Repository\InvitationRepository;
use Psr\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

final class InvitationController extends AppController
{
    use TargetPathTrait;

    public function __construct(
        private readonly InvitationRepository $invitations,
        private readonly ClockInterface $clock,
    ) {
    }

    #[Route('/invite/{token}', name: 'invitation_show', methods: ['GET'], requirements: ['token' => '[a-f0-9]{40}'])]
    public function show(string $token, Request $request): Response
    {
        $invitation = $this->invitations->findOneByToken($token);
        $usable = null !== $invitation && $invitation->isUsable($this->clock->now());

        if ($usable && null === $this->getUser()) {
            // come back here after signing in or creating an account
            $this->saveTargetPath($request->getSession(), 'main', $request->getUri());
        }

        return $this->render('invitation/show.html.twig', [
            'invitation' => $usable ? $invitation : null,
            'token' => $token,
        ], new Response(status: $usable ? 200 : 410));
    }

    #[Route('/invite/{token}/accept', name: 'invitation_accept', methods: ['POST'], requirements: ['token' => '[a-f0-9]{40}'])]
    public function accept(string $token, Request $request, MemberManager $manager, CurrentHousehold $current, #[CurrentUser] ?User $user): Response
    {
        if (null === $user) {
            return $this->redirectToRoute('app_login');
        }
        $this->assertCsrf('accept-invitation', $request);

        $invitation = $this->invitations->findOneByToken($token) ?? throw $this->createNotFoundException();
        try {
            $membership = $manager->accept($invitation, $user, $this->clock->now());
        } catch (MembershipException $e) {
            $this->addFlash('error', $e->getMessage());

            return $this->redirectToRoute('home');
        }

        $current->switchTo($membership->getHousehold());
        $this->addFlash('success', \sprintf('Welcome to %s!', $membership->getHousehold()->getName()));

        return $this->redirectToRoute('home');
    }
}
