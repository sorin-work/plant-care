<?php

namespace App\Care;

use App\Entity\Plant;
use DateTimeImmutable;

final class PlantSorter
{
    public const NAME = 'name';
    public const NEXT_DUE = 'due';
    public const NEWEST = 'newest';

    public const ALL = [
        self::NAME => 'Name',
        self::NEXT_DUE => 'Next care',
        self::NEWEST => 'Recently added',
    ];

    /**
     * @param list<Plant> $plants
     *
     * @return list<Plant>
     */
    public function sort(array $plants, string $by): array
    {
        $compare = match ($by) {
            self::NEXT_DUE => static function (Plant $a, Plant $b): int {
                $da = self::effectiveDue($a);
                $db = self::effectiveDue($b);
                if (null === $da || null === $db) {
                    return (null === $da) <=> (null === $db);
                }

                return [$da, $a->getName()] <=> [$db, $b->getName()];
            },
            self::NEWEST => static fn (Plant $a, Plant $b): int => [$b->getCreatedAt(), $b->getId()] <=> [$a->getCreatedAt(), $a->getId()],
            default => static fn (Plant $a, Plant $b): int => strnatcasecmp($a->getName(), $b->getName()),
        };

        usort($plants, $compare);

        return $plants;
    }

    /**
     * The first day the plant needs something, taking snoozes into account.
     */
    private static function effectiveDue(Plant $plant): ?DateTimeImmutable
    {
        $first = null;
        foreach ($plant->getSchedules() as $schedule) {
            $due = max($schedule->getNextDueOn(), $schedule->getSnoozedUntil() ?? $schedule->getNextDueOn());
            if (null === $first || $due < $first) {
                $first = $due;
            }
        }

        return $first;
    }
}
