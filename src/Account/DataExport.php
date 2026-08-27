<?php

namespace App\Account;

use App\Entity\User;
use App\Repository\CareEventRepository;
use App\Repository\JournalEntryRepository;
use App\Repository\MembershipRepository;
use App\Repository\PlantRepository;
use DateTimeImmutable;

/**
 * Everything we store about a user and their households, as one array that
 * can be downloaded as JSON.
 */
final class DataExport
{
    public function __construct(
        private readonly MembershipRepository $memberships,
        private readonly PlantRepository $plants,
        private readonly JournalEntryRepository $journal,
        private readonly CareEventRepository $events,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function forUser(User $user): array
    {
        $households = [];
        foreach ($this->memberships->findForUser($user) as $membership) {
            $household = $membership->getHousehold();
            $plants = [];
            foreach ($this->plants->findBy(['household' => $household], ['name' => 'ASC']) as $plant) {
                $plants[] = [
                    'name' => $plant->getName(),
                    'species' => $plant->getSpecies()?->getScientificName(),
                    'room' => $plant->getRoom()?->getName(),
                    'light' => $plant->getLight()->value,
                    'acquiredOn' => $plant->getAcquiredOn()?->format('Y-m-d'),
                    'archivedAt' => $plant->getArchivedAt()?->format(\DATE_ATOM),
                    'notes' => $plant->getNotes(),
                    'schedules' => array_map(static fn ($s) => [
                        'type' => $s->getType()->value,
                        'intervalDays' => $s->getIntervalDays(),
                        'seasonal' => $s->isSeasonal(),
                        'nextDueOn' => $s->getNextDueOn()->format('Y-m-d'),
                        'lastDoneOn' => $s->getLastDoneOn()?->format('Y-m-d'),
                        'notes' => $s->getNotes(),
                    ], $plant->getSchedules()->getValues()),
                    'journal' => array_map(static fn ($e) => [
                        'date' => $e->getWrittenOn()->format('Y-m-d'),
                        'author' => $e->getAuthor()?->getDisplayName(),
                        'note' => $e->getNote(),
                        'photo' => $e->getPhoto(),
                    ], $this->journal->findBy(['plant' => $plant], ['writtenOn' => 'ASC'])),
                    'care' => array_map(static fn ($e) => [
                        'type' => $e->getType()->value,
                        'action' => $e->getAction()->value,
                        'on' => $e->getHappenedOn()->format('Y-m-d'),
                        'by' => $e->getUser()?->getDisplayName(),
                    ], $this->events->findBy(['plant' => $plant], ['happenedOn' => 'ASC'])),
                ];
            }

            $households[] = [
                'name' => $household->getName(),
                'role' => $membership->getRole()->value,
                'timezone' => $household->getTimezone(),
                'hemisphere' => $household->getHemisphere()->value,
                'plants' => $plants,
            ];
        }

        return [
            'exportedAt' => (new DateTimeImmutable())->format(\DATE_ATOM),
            'account' => [
                'email' => $user->getEmail(),
                'displayName' => $user->getDisplayName(),
                'createdAt' => $user->getCreatedAt()->format(\DATE_ATOM),
                'dailyEmail' => $user->isDigestEnabled(),
            ],
            'households' => $households,
        ];
    }
}
