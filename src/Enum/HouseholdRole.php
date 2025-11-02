<?php

namespace App\Enum;

enum HouseholdRole: string
{
    case Owner = 'owner';
    case Editor = 'editor';
    case Viewer = 'viewer';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
