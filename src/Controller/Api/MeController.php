<?php

namespace App\Controller\Api;

use App\Entity\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class MeController extends ApiController
{
    #[Route('/api/me', name: 'api_me', methods: ['GET'])]
    public function __invoke(#[CurrentUser] User $user): JsonResponse
    {
        $households = [];
        foreach ($this->memberships->findForUser($user) as $membership) {
            $h = $membership->getHousehold();
            $households[] = [
                'id' => $h->getId(),
                'name' => $h->getName(),
                'role' => $membership->getRole()->value,
                'timezone' => $h->getTimezone(),
                'hemisphere' => $h->getHemisphere()->value,
            ];
        }

        return $this->json([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'displayName' => $user->getDisplayName(),
            'households' => $households,
        ]);
    }
}
