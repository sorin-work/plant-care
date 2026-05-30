<?php

namespace App\Tests\Functional\Household;

use App\Entity\Invitation;
use App\Enum\HouseholdRole;
use App\Household\MemberManager;
use App\Household\MembershipException;
use App\Tests\Support\Factory;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class MemberManagerTest extends KernelTestCase
{
    use Factory;

    private function manager(): MemberManager
    {
        return static::getContainer()->get(MemberManager::class);
    }

    public function testAddIsCaseInsensitiveOnEmail(): void
    {
        self::bootKernel();
        $household = $this->createHousehold($this->createUser('owner@example.com'));
        $this->createUser('friend@example.com', 'Fred');

        $membership = $this->manager()->add($household, ' FRIEND@example.com', HouseholdRole::Viewer);

        self::assertSame('Fred', $membership->getUser()->getDisplayName());
    }

    public function testSecondOwnerCanBeDemoted(): void
    {
        self::bootKernel();
        $household = $this->createHousehold($this->createUser('owner@example.com'));
        $second = $this->addMember($household, $this->createUser('second@example.com'), HouseholdRole::Owner);

        $this->manager()->changeRole($second, HouseholdRole::Editor);

        self::assertSame(HouseholdRole::Editor, $second->getRole());
    }

    public function testLastOwnerCannotBeRemoved(): void
    {
        self::bootKernel();
        $owner = $this->createUser('owner@example.com');
        $household = $this->createHousehold($owner);
        $this->addMember($household, $this->createUser('editor@example.com'), HouseholdRole::Editor);
        $membership = static::getContainer()->get(\App\Repository\MembershipRepository::class)->findOneFor($household, $owner);
        \assert(null !== $membership);

        $this->expectException(MembershipException::class);
        $this->manager()->remove($membership);
    }

    public function testInvitationCannotBeAcceptedTwice(): void
    {
        self::bootKernel();
        $owner = $this->createUser('owner@example.com');
        $household = $this->createHousehold($owner);
        [$invitation] = $this->manager()->invite($household, HouseholdRole::Viewer, $owner);
        $this->manager()->accept($invitation, $this->createUser('a@example.com'), new DateTimeImmutable());

        $this->expectException(MembershipException::class);
        $this->manager()->accept($invitation, $this->createUser('b@example.com'), new DateTimeImmutable());
    }

    public function testMemberCannotAcceptInvitationToTheirOwnHousehold(): void
    {
        self::bootKernel();
        $owner = $this->createUser('owner@example.com');
        $household = $this->createHousehold($owner);
        [$invitation] = $this->manager()->invite($household, HouseholdRole::Viewer, $owner);

        $this->expectExceptionMessage('already a member');
        $this->manager()->accept($invitation, $owner, new DateTimeImmutable());
    }

    public function testExpiredInvitationIsRejected(): void
    {
        self::bootKernel();
        $owner = $this->createUser('owner@example.com');
        $invitation = new Invitation($this->createHousehold($owner), HouseholdRole::Viewer, str_repeat('a', 40), $owner, new DateTimeImmutable('2026-01-01'));

        $this->expectExceptionMessage('expired');
        $this->manager()->accept($invitation, $this->createUser('late@example.com'), new DateTimeImmutable('2026-01-09'));
    }
}
