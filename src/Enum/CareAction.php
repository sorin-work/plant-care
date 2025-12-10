<?php

namespace App\Enum;

enum CareAction: string
{
    case Done = 'done';
    case Skipped = 'skipped';
    case Snoozed = 'snoozed';
}
