<?php

namespace App\Controller\Api;

use App\Repository\PlantRepository;
use App\Util\Initials;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class PlantController extends ApiController
{
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
