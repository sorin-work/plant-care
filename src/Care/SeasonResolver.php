<?php

namespace App\Care;

use App\Enum\Hemisphere;
use App\Enum\Season;
use DateTimeInterface;

/**
 * Meteorological seasons: winter is December to February in the north.
 */
final class SeasonResolver
{
    public function seasonOn(DateTimeInterface $date, Hemisphere $hemisphere = Hemisphere::North): Season
    {
        $month = (int) $date->format('n');

        $season = match (true) {
            $month >= 12 || $month < 2 => Season::Winter,
            $month < 6 => Season::Spring,
            $month < 9 => Season::Summer,
            default => Season::Autumn,
        };

        return Hemisphere::South === $hemisphere ? $season->opposite() : $season;
    }
}
