<?php

namespace App\Twig;

use App\Entity\Household;
use App\Entity\Membership;
use App\Household\CurrentHousehold;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class HouseholdExtension extends AbstractExtension
{
    public function __construct(private readonly CurrentHousehold $current)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('current_household', fn (): Household => $this->current->get()),
            new TwigFunction('current_membership', fn (): Membership => $this->current->membership()),
            new TwigFunction('my_households', fn (): array => $this->current->all()),
        ];
    }
}
