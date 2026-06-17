<?php

namespace App\Controller\Api;

use App\Entity\Plant;
use App\Repository\CareEventRepository;
use App\Repository\PlantRepository;
use App\Security\Permission;
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
            'recentCare' => array_map(Presenter::careEvent(...), $events->findRecentForPlant($plant)),
        ]);
    }

    #[Route('/api/plants', name: 'api_plants', methods: ['GET'])]
    public function index(Request $request, PlantRepository $plants): JsonResponse
    {
        $household = $this->household($request);

        return $this->json([
            'household' => $household->getId(),
            'plants' => array_map(Presenter::plantSummary(...), $plants->findActive($household)),
        ]);
    }
}
