<?php

namespace App\Controller\Api;

use App\Entity\JournalEntry;
use App\Entity\Plant;
use App\Repository\JournalEntryRepository;
use App\Security\Permission;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class JournalController extends ApiController
{
    #[Route('/api/plants/{id}/journal', name: 'api_journal', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function index(Plant $plant, Request $request, JournalEntryRepository $entries): JsonResponse
    {
        $this->guard(Permission::VIEW, $plant);
        $page = $entries->pageForPlant($plant, $request->query->getInt('page', 1));

        return $this->json([
            'page' => $page->number,
            'pages' => $page->pageCount(),
            'total' => $page->total,
            'entries' => array_map($this->entry(...), $page->items),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function entry(JournalEntry $entry): array
    {
        return [
            'id' => $entry->getId(),
            'date' => $entry->getWrittenOn()->format('Y-m-d'),
            'author' => $entry->getAuthor()?->getDisplayName(),
            'note' => $entry->getNote(),
            'photo' => null === $entry->getPhoto() ? null : $this->generateUrl('photo_show', ['name' => $entry->getPhoto()], UrlGeneratorInterface::ABSOLUTE_URL),
        ];
    }
}
