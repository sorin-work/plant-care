<?php

namespace App\Household;

use DomainException;

/**
 * A membership change that is not allowed; the message is shown to the user.
 */
final class MembershipException extends DomainException
{
}
