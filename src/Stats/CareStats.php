<?php

namespace App\Stats;

use App\Entity\CareEvent;
use App\Entity\Household;
use App\Enum\CareAction;
use App\Repository\CareEventRepository;
use DateTimeImmutable;

/**
 * Numbers for the stats page: what was done over the last weeks.
 */
final class CareStats
{
    public const WEEKS = 8;

    public function __construct(private readonly CareEventRepository $events)
    {
    }

    /**
     * @return array{
     *     byType: array<string, array{done: int, skipped: int}>,
     *     weeks: list<array{start: DateTimeImmutable, done: int}>,
     *     topPlants: list<array{name: string, id: int|null, done: int}>,
     *     total: int,
     * }
     */
    public function forHousehold(Household $household, DateTimeImmutable $today): array
    {
        $today = $today->setTime(0, 0);
        $firstWeek = $today->modify('monday this week')->modify(\sprintf('-%d weeks', self::WEEKS - 1));
        $events = $this->events->findForHouseholdBetween($household, $firstWeek, $today);

        $weeks = [];
        for ($i = 0; $i < self::WEEKS; ++$i) {
            $weeks[] = ['start' => $firstWeek->modify(\sprintf('+%d weeks', $i)), 'done' => 0];
        }

        $byType = [];
        $plants = [];
        $total = 0;
        foreach ($events as $event) {
            $type = $event->getType()->value;
            $byType[$type] ??= ['done' => 0, 'skipped' => 0];

            if (CareAction::Done === $event->getAction()) {
                ++$byType[$type]['done'];
                ++$total;
                $week = intdiv((int) $firstWeek->diff($event->getHappenedOn())->format('%a'), 7);
                if (isset($weeks[$week])) {
                    ++$weeks[$week]['done'];
                }
                $this->countPlant($plants, $event);
            } elseif (CareAction::Skipped === $event->getAction()) {
                ++$byType[$type]['skipped'];
            }
        }

        usort($plants, static fn (array $a, array $b): int => [$b['done'], $a['name']] <=> [$a['done'], $b['name']]);
        ksort($byType);

        return [
            'byType' => $byType,
            'weeks' => $weeks,
            'topPlants' => \array_slice($plants, 0, 5),
            'total' => $total,
        ];
    }

    /**
     * @param array<int, array{name: string, id: int|null, done: int}> $plants
     */
    private function countPlant(array &$plants, CareEvent $event): void
    {
        $plant = $event->getPlant();
        $key = spl_object_id($plant);
        $plants[$key] ??= ['name' => $plant->getName(), 'id' => $plant->getId(), 'done' => 0];
        ++$plants[$key]['done'];
    }
}
