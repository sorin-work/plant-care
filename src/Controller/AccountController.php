<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/account')]
final class AccountController extends AppController
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    #[Route('', name: 'account', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('account/index.html.twig');
    }

    #[Route('/api-token', name: 'account_token', methods: ['POST'])]
    public function generateToken(Request $request, #[CurrentUser] User $user): Response
    {
        $this->assertCsrf('api-token', $request);
        $token = $user->generateApiToken();
        $this->em->flush();

        return $this->render('account/index.html.twig', ['new_token' => $token]);
    }

    #[Route('/api-token/revoke', name: 'account_token_revoke', methods: ['POST'])]
    public function revokeToken(Request $request, #[CurrentUser] User $user): Response
    {
        $this->assertCsrf('api-token', $request);
        $user->revokeApiToken();
        $this->em->flush();
        $this->addFlash('success', 'Your API token was revoked.');

        return $this->redirectToRoute('account');
    }
}
