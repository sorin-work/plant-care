<?php

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

final class HealthController
{
    public function __construct(private readonly Connection $connection)
    {
    }

    #[Route('/health', name: 'health', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        try {
            $this->connection->executeQuery('SELECT 1')->fetchOne();
        } catch (Throwable) {
            return new JsonResponse(['status' => 'error', 'database' => 'down'], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        return new JsonResponse(['status' => 'ok', 'database' => 'up']);
    }
}
