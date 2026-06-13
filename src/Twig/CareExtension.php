<?php

namespace App\Twig;

use App\Care\CareScheduler;
use App\Care\SeasonResolver;
use App\Entity\CareSchedule;
use App\Household\HouseholdClock;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class CareExtension extends AbstractExtension
{
    public function __construct(
        private readonly CareScheduler $scheduler,
        private readonly SeasonResolver $seasons,
        private readonly HouseholdClock $clock,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('current_interval', $this->currentInterval(...)),
            new TwigFunction('current_season', $this->currentSeason(...)),
        ];
    }

    /**
     * The interval that applies if the task is done today.
     */
    public function currentInterval(CareSchedule $schedule): int
    {
        return $this->scheduler->intervalOn($schedule, $this->clock->today($schedule->getPlant()->getHousehold()));
    }

    public function currentSeason(CareSchedule $schedule): string
    {
        $household = $schedule->getPlant()->getHousehold();

        return $this->seasons->seasonOn($this->clock->today($household), $household->getHemisphere())->value;
    }
}
