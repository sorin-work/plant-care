<?php

namespace App\Controller\Api;

use App\Entity\JournalEntry;
use App\Entity\Plant;
use App\Entity\User;
use App\Household\HouseholdClock;
use App\Repository\JournalEntryRepository;
use App\Security\Permission;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Validator\Validator\ValidatorInterface;

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

    #[Route('/api/plants/{id}/journal', name: 'api_journal_add', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function add(Plant $plant, Request $request, ValidatorInterface $validator, EntityManagerInterface $em, HouseholdClock $clock, #[CurrentUser] User $user): JsonResponse
    {
        $this->guard(Permission::CARE, $plant);
        $data = $request->toArray();

        $day = $clock->today($plant->getHousehold());
        if (isset($data['date'])) {
            $parsed = \is_string($data['date']) ? DateTimeImmutable::createFromFormat('!Y-m-d', $data['date']) : false;
            if (false === $parsed) {
                return $this->json(['error' => '"date" must look like 2026-06-14.'], 422);
            }
            $day = $parsed;
        }

        $entry = (new JournalEntry($plant, $user, $day))->setNote(\is_string($data['note'] ?? null) ? $data['note'] : '');
        $violations = $validator->validate($entry);
        if (\count($violations) > 0) {
            return $this->json(['error' => (string) $violations[0]?->getMessage()], 422);
        }

        $em->persist($entry);
        $em->flush();

        return $this->json($this->entry($entry), 201);
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
