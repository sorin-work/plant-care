<?php

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

final class HealthController
{
    public function __construct(
        private readonly Connection $connection,
        #[Autowire('%app.version%')]
        private readonly string $version,
        #[Autowire('%env(resolve:PHOTO_DIR)%')]
        private readonly string $photoDir,
    ) {
    }

    #[Route('/health', name: 'health', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        try {
            $this->connection->executeQuery('SELECT 1')->fetchOne();
            $database = 'up';
        } catch (Throwable) {
            $database = 'down';
        }

        // the directory is created on the first upload, so a missing one is fine
        // as long as its parent is writable
        $dir = is_dir($this->photoDir) ? $this->photoDir : \dirname($this->photoDir);
        $photos = is_writable($dir) ? 'writable' : 'read-only';

        $ok = 'up' === $database && 'writable' === $photos;

        return new JsonResponse([
            'status' => $ok ? 'ok' : 'error',
            'database' => $database,
            'photos' => $photos,
            'version' => $this->version,
        ], $ok ? Response::HTTP_OK : Response::HTTP_SERVICE_UNAVAILABLE);
    }
}
