<?php

namespace App\Care;

use App\Enum\CareTaskType;
use App\Enum\Season;
use InvalidArgumentException;

/**
 * Stretches care intervals in winter, when plants grow slowly and need less
 * water and food, and shrinks them in summer.
 */
final class SeasonalIntervalCalculator
{
    /**
     * Multipliers applied to the base interval, per task type and season.
     *
     * @var array<string, array<string, float>>
     */
    private const FACTORS = [
        'water' => ['winter' => 1.5, 'spring' => 1.0, 'summer' => 0.75, 'autumn' => 1.2],
        'fertilize' => ['winter' => 2.0, 'spring' => 1.0, 'summer' => 1.0, 'autumn' => 1.5],
        'repot' => ['winter' => 1.0, 'spring' => 1.0, 'summer' => 1.0, 'autumn' => 1.0],
        // Heated rooms are dry in winter, so humidity lovers want misting more often.
        'mist' => ['winter' => 0.5, 'spring' => 1.0, 'summer' => 1.0, 'autumn' => 1.0],
    ];

    public function factor(CareTaskType $type, Season $season): float
    {
        return self::FACTORS[$type->value][$season->value];
    }

    public function effectiveInterval(CareTaskType $type, int $baseDays, Season $season): int
    {
        if ($baseDays < 1) {
            throw new InvalidArgumentException('The base interval must be at least one day.');
        }

        return (int) ($baseDays * $this->factor($type, $season));
    }
}
