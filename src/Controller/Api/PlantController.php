<?php

namespace App\Controller\Api;

use App\Entity\Plant;
use App\Repository\CareEventRepository;
use App\Repository\PlantRepository;
use App\Security\Permission;
use App\Util\Initials;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class PlantController extends ApiController
{
    #[Route('/api/plants/{id}', name: 'api_plant', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Plant $plant, CareEventRepository $events): JsonResponse
    {
        $this->guard(Permission::VIEW, $plant);

        return $this->json([
            'id' => $plant->getId(),
            'name' => $plant->getName(),
            'species' => $plant->getSpecies()?->getScientificName(),
            'room' => $plant->getRoom()?->getName(),
            'light' => $plant->getLight()->value,
            'notes' => $plant->getNotes(),
            'archived' => $plant->isArchived(),
            'schedules' => array_map(Presenter::schedule(...), $plant->getSchedules()->getValues()),
            'recentCare' => array_map(static fn ($e) => [
                'type' => $e->getType()->value,
                'action' => $e->getAction()->value,
                'on' => $e->getHappenedOn()->format('Y-m-d'),
                'by' => $e->getUser()?->getDisplayName(),
            ], $events->findRecentForPlant($plant)),
        ]);
    }

    #[Route('/api/plants', name: 'api_plants', methods: ['GET'])]
    public function index(Request $request, PlantRepository $plants): JsonResponse
    {
        $household = $this->household($request);
        $data = [];
        foreach ($plants->findActive($household) as $plant) {
            $data[] = [
                'id' => $plant->getId(),
                'name' => $plant->getName(),
                'initials' => Initials::of($plant->getName()),
                'species' => $plant->getSpecies()?->getScientificName(),
                'room' => $plant->getRoom()?->getName(),
                'light' => $plant->getLight()->value,
                'acquiredOn' => $plant->getAcquiredOn()?->format('Y-m-d'),
                'hasPhoto' => null !== $plant->getCoverPhoto(),
            ];
        }

        return $this->json(['household' => $household->getId(), 'plants' => $data]);
    }
}
