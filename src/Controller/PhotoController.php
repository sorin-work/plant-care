<?php

namespace App\Controller;

use App\Entity\User;
use App\Photo\PhotoStorage;
use App\Repository\JournalEntryRepository;
use App\Repository\MembershipRepository;
use App\Repository\PlantRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

/**
 * Photos live outside the public directory and are only served to members
 * of the household the plant belongs to.
 */
final class PhotoController extends AbstractController
{
    #[Route('/photos/{name}', name: 'photo_show', methods: ['GET'], requirements: ['name' => '[a-f0-9]{32}\.(jpg|png|webp)'])]
    public function show(
        string $name,
        PhotoStorage $storage,
        JournalEntryRepository $entries,
        PlantRepository $plants,
        MembershipRepository $memberships,
        #[CurrentUser] User $user,
    ): Response {
        $plant = $entries->findOneBy(['photo' => $name])?->getPlant() ?? $plants->findOneBy(['coverPhoto' => $name]);
        $household = $plant?->getHousehold();

        if (null === $household || null === $memberships->findOneFor($household, $user) || !$storage->exists($name)) {
            throw $this->createNotFoundException();
        }

        $response = $this->file($storage->path($name), null, ResponseHeaderBag::DISPOSITION_INLINE);
        $response->setPrivate();
        $response->setMaxAge(86400);

        return $response;
    }
}
