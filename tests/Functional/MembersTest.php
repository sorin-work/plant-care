<?php

namespace App\Tests\Functional;

use App\Enum\HouseholdRole;
use App\Repository\MembershipRepository;
use App\Tests\Support\Factory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class MembersTest extends WebTestCase
{
    use Factory;

    public function testListsMembersWithRoles(): void
    {
        $client = static::createClient();
        $owner = $this->createUser('owner@example.com', 'Olga');
        $household = $this->createHousehold($owner, 'Flat');
        $this->addMember($household, $this->createUser('vic@example.com', 'Victor'), HouseholdRole::Viewer);
        $client->loginUser($owner);

        $crawler = $client->request('GET', '/household/members');

        self::assertResponseIsSuccessful();
        $rows = $crawler->filter('.members li')->each(fn ($li) => $li->text());
        self::assertCount(2, $rows);
        self::assertStringContainsString('Olga', $rows[0]);
        self::assertStringContainsString('Owner', $rows[0]);
        self::assertStringContainsString('Victor', $rows[1]);
        self::assertStringContainsString('Viewer', $rows[1]);
    }

    public function testOwnerAddsMemberByEmail(): void
    {
        $client = static::createClient();
        $owner = $this->createUser('owner@example.com', 'Olga');
        $household = $this->createHousehold($owner, 'Flat');
        $friend = $this->createUser('friend@example.com', 'Fred');
        $client->loginUser($owner);

        $client->request('GET', '/household/members');
        $client->submitForm('Add member', [
            'add_member[email]' => 'Friend@Example.com',
            'add_member[role]' => 'viewer',
        ]);
        self::assertResponseRedirects('/household/members');
        $client->followRedirect();

        self::assertSelectorTextContains('.flash', 'Fred joined Flat as viewer.');
        $membership = static::getContainer()->get(MembershipRepository::class)->findOneFor($household, $friend);
        self::assertSame(HouseholdRole::Viewer, $membership?->getRole());
    }

    public function testUnknownEmailIsReported(): void
    {
        $client = static::createClient();
        $owner = $this->createUser('owner@example.com');
        $this->createHousehold($owner);
        $client->loginUser($owner);

        $client->request('GET', '/household/members');
        $client->submitForm('Add member', ['add_member[email]' => 'nobody@example.com']);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('.add-member', 'no account with this email');
    }

    public function testAddingExistingMemberIsReported(): void
    {
        $client = static::createClient();
        $owner = $this->createUser('owner@example.com', 'Olga');
        $this->createHousehold($owner);
        $client->loginUser($owner);

        $client->request('GET', '/household/members');
        $client->submitForm('Add member', ['add_member[email]' => 'owner@example.com']);

        self::assertSelectorTextContains('.add-member', 'Olga is already a member.');
    }

    public function testEditorCannotAddMembers(): void
    {
        $client = static::createClient();
        $owner = $this->createUser('owner@example.com');
        $household = $this->createHousehold($owner);
        $editor = $this->createUser('editor@example.com');
        $this->addMember($household, $editor, HouseholdRole::Editor);
        $client->loginUser($editor);

        $crawler = $client->request('GET', '/household/members');
        self::assertSelectorNotExists('.add-member');

        $client->request('POST', '/household/members', ['add_member' => ['email' => 'x@example.com', 'role' => 'owner']]);
        self::assertResponseStatusCodeSame(403);
    }

    public function testOwnerChangesRole(): void
    {
        $client = static::createClient();
        $owner = $this->createUser('owner@example.com', 'Olga');
        $household = $this->createHousehold($owner);
        $membership = $this->addMember($household, $this->createUser('vic@example.com', 'Victor'), HouseholdRole::Viewer);
        $client->loginUser($owner);

        $crawler = $client->request('GET', '/household/members');
        $form = $crawler->filter('.role-form')->eq(1)->form(['role' => 'editor']);
        $client->submit($form);
        $client->followRedirect();

        self::assertSelectorTextContains('.flash', 'Victor is now editor.');
        self::assertSame(HouseholdRole::Editor, $this->em()->find(\App\Entity\Membership::class, $membership->getId())?->getRole());
    }

    public function testLastOwnerCannotBeDemoted(): void
    {
        $client = static::createClient();
        $owner = $this->createUser('owner@example.com', 'Olga');
        $this->createHousehold($owner);
        $client->loginUser($owner);

        $crawler = $client->request('GET', '/household/members');
        $client->submit($crawler->filter('.role-form')->first()->form(['role' => 'viewer']));
        $client->followRedirect();

        self::assertSelectorTextContains('.flash-error', 'at least one owner');
    }

    public function testOwnerRemovesMember(): void
    {
        $client = static::createClient();
        $owner = $this->createUser('owner@example.com', 'Olga');
        $household = $this->createHousehold($owner);
        $victor = $this->createUser('vic@example.com', 'Victor');
        $this->addMember($household, $victor, HouseholdRole::Editor);
        $client->loginUser($owner);

        $client->request('GET', '/household/members');
        $client->submitForm('Remove');
        $crawler = $client->followRedirect();

        self::assertSelectorTextContains('.flash', 'Victor was removed');
        self::assertCount(1, $crawler->filter('.members li'));
        self::assertNull(static::getContainer()->get(MembershipRepository::class)->findOneFor($household, $victor));
    }

    public function testEditorCannotChangeRoles(): void
    {
        $client = static::createClient();
        $owner = $this->createUser('owner@example.com');
        $household = $this->createHousehold($owner);
        $editor = $this->createUser('editor@example.com');
        $membership = $this->addMember($household, $editor, HouseholdRole::Editor);
        $client->loginUser($editor);

        $client->request('POST', '/household/members/'.$membership->getId().'/role', ['role' => 'owner', '_token' => 'x']);

        self::assertResponseStatusCodeSame(403);
    }
}
