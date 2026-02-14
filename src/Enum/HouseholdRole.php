<?php

namespace App\Enum;

enum HouseholdRole: string
{
    case Owner = 'owner';
    case Editor = 'editor';
    /** Looks after the plants (e.g. while you are on holiday) but cannot change them. */
    case Caretaker = 'caretaker';
    case Viewer = 'viewer';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
