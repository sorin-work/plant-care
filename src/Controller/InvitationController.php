<?php

namespace App\Controller;

use App\Repository\InvitationRepository;
use Psr\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class InvitationController extends AppController
{
    public function __construct(
        private readonly InvitationRepository $invitations,
        private readonly ClockInterface $clock,
    ) {
    }

    #[Route('/invite/{token}', name: 'invitation_show', methods: ['GET'], requirements: ['token' => '[a-f0-9]{40}'])]
    public function show(string $token): Response
    {
        $invitation = $this->invitations->findOneByToken($token);
        $usable = null !== $invitation && $invitation->isUsable($this->clock->now());

        return $this->render('invitation/show.html.twig', [
            'invitation' => $usable ? $invitation : null,
            'token' => $token,
        ], new Response(status: $usable ? 200 : 410));
    }
}
