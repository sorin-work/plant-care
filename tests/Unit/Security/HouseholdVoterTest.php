<?php

namespace App\Tests\Unit\Security;

use App\Entity\Household;
use App\Entity\Membership;
use App\Entity\Plant;
use App\Entity\User;
use App\Enum\HouseholdRole;
use App\Repository\MembershipRepository;
use App\Security\HouseholdVoter;
use App\Security\Permission;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

final class HouseholdVoterTest extends TestCase
{
    /**
     * @return iterable<string, array{?HouseholdRole, string, int}>
     */
    public static function cases(): iterable
    {
        $granted = VoterInterface::ACCESS_GRANTED;
        $denied = VoterInterface::ACCESS_DENIED;

        yield 'owner can manage' => [HouseholdRole::Owner, Permission::MANAGE, $granted];
        yield 'owner can edit' => [HouseholdRole::Owner, Permission::EDIT, $granted];
        yield 'editor can edit' => [HouseholdRole::Editor, Permission::EDIT, $granted];
        yield 'editor can care' => [HouseholdRole::Editor, Permission::CARE, $granted];
        yield 'editor cannot manage' => [HouseholdRole::Editor, Permission::MANAGE, $denied];
        yield 'caretaker can care' => [HouseholdRole::Caretaker, Permission::CARE, $granted];
        yield 'caretaker cannot edit' => [HouseholdRole::Caretaker, Permission::EDIT, $denied];
        yield 'viewer can view' => [HouseholdRole::Viewer, Permission::VIEW, $granted];
        yield 'viewer cannot care' => [HouseholdRole::Viewer, Permission::CARE, $denied];
        yield 'viewer cannot edit' => [HouseholdRole::Viewer, Permission::EDIT, $denied];
        yield 'stranger cannot view' => [null, Permission::VIEW, $denied];
    }

    #[DataProvider('cases')]
    public function testVote(?HouseholdRole $role, string $attribute, int $expected): void
    {
        $household = new Household('Home');
        $user = (new User())->setEmail('u@example.com');
        $repo = $this->createStub(MembershipRepository::class);
        $repo->method('findOneFor')->willReturn(null === $role ? null : new Membership($household, $user, $role));

        $voter = new HouseholdVoter($repo);
        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());

        self::assertSame($expected, $voter->vote($token, new Plant($household), [$attribute]));
    }

    public function testAbstainsForUnrelatedSubjects(): void
    {
        $voter = new HouseholdVoter($this->createStub(MembershipRepository::class));
        $user = (new User())->setEmail('u@example.com');
        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());

        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $voter->vote($token, new stdClass(), [Permission::VIEW]));
        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $voter->vote($token, new Household('x'), ['ROLE_ADMIN']));
    }
}
