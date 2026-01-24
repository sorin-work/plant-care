<?php

namespace App\Security;

/**
 * Attributes understood by HouseholdVoter.
 */
final class Permission
{
    /** See plants, schedules, journal and photos. */
    public const VIEW = 'HOUSEHOLD_VIEW';

    /** Mark tasks done, snooze or skip them and write journal entries. */
    public const CARE = 'HOUSEHOLD_CARE';

    /** Add, edit and archive plants, rooms and schedules. */
    public const EDIT = 'HOUSEHOLD_EDIT';

    /** Change settings and manage members. */
    public const MANAGE = 'HOUSEHOLD_MANAGE';

    public const ALL = [self::VIEW, self::CARE, self::EDIT, self::MANAGE];
}
