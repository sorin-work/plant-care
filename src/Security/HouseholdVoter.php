<?php

namespace App\Security;

use App\Entity\CareSchedule;
use App\Entity\Household;
use App\Entity\JournalEntry;
use App\Entity\Plant;
use App\Entity\Room;
use App\Entity\User;
use App\Enum\HouseholdRole;
use App\Repository\MembershipRepository;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Decides what a user may do with a household and everything in it, based on
 * their membership role.
 *
 * @extends Voter<string, Household|Plant|Room|CareSchedule|JournalEntry>
 */
final class HouseholdVoter extends Voter
{
    private const GRANTS = [
        Permission::VIEW => [HouseholdRole::Owner, HouseholdRole::Editor, HouseholdRole::Caretaker, HouseholdRole::Viewer],
        Permission::CARE => [HouseholdRole::Owner, HouseholdRole::Editor, HouseholdRole::Caretaker],
        Permission::EDIT => [HouseholdRole::Owner, HouseholdRole::Editor],
        Permission::MANAGE => [HouseholdRole::Owner],
    ];

    public function __construct(private readonly MembershipRepository $memberships)
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return \in_array($attribute, Permission::ALL, true) && null !== self::householdOf($subject);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();
        $household = self::householdOf($subject);
        if (!$user instanceof User || null === $household) {
            return false;
        }

        $membership = $this->memberships->findOneFor($household, $user);
        if (null === $membership) {
            $vote?->addReason('Not a member of this household.');

            return false;
        }

        return \in_array($membership->getRole(), self::GRANTS[$attribute], true);
    }

    public static function householdOf(mixed $subject): ?Household
    {
        return match (true) {
            $subject instanceof Household => $subject,
            $subject instanceof Plant, $subject instanceof Room => $subject->getHousehold(),
            $subject instanceof CareSchedule, $subject instanceof JournalEntry => $subject->getPlant()->getHousehold(),
            default => null,
        };
    }
}
