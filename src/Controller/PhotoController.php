<?php

namespace App\Controller;

use App\Photo\PhotoStorage;
use App\Repository\JournalEntryRepository;
use App\Repository\PlantRepository;
use App\Security\Permission;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Photos live outside the public directory and are only served to members
 * of the household the plant belongs to.
 */
final class PhotoController extends AppController
{
    #[Route('/photos/{name}', name: 'photo_show', methods: ['GET'], requirements: ['name' => '[a-f0-9]{32}\.(jpg|png|webp)'])]
    public function show(
        string $name,
        PhotoStorage $storage,
        JournalEntryRepository $entries,
        PlantRepository $plants,
    ): Response {
        $plant = $entries->findOneBy(['photo' => $name])?->getPlant() ?? $plants->findOneBy(['coverPhoto' => $name]);
        if (null === $plant || !$this->isGranted(Permission::VIEW, $plant) || !$storage->exists($name)) {
            throw $this->createNotFoundException();
        }

        $response = $this->file($storage->path($name), null, ResponseHeaderBag::DISPOSITION_INLINE);
        // The stored extension decides the type, never the file content.
        $response->headers->set('Content-Type', PhotoStorage::contentType($name));
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Content-Security-Policy', "default-src 'none'; sandbox");
        $response->setPrivate();
        $response->setMaxAge(86400);

        return $response;
    }
}
