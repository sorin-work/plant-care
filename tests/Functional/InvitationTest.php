<?php

namespace App\Tests\Functional;

use App\Entity\Invitation;
use App\Enum\HouseholdRole;
use App\Tests\Support\Factory;
use DateTimeImmutable;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class InvitationTest extends WebTestCase
{
    use Factory;

    private function invitation(string $token = 'ab', string $created = 'now'): Invitation
    {
        $owner = $this->createUser('owner@example.com', 'Olga');
        $household = $this->createHousehold($owner, 'Flat');
        $invitation = new Invitation($household, HouseholdRole::Editor, str_pad($token, 40, '0'), $owner, new DateTimeImmutable($created));
        $this->em()->persist($invitation);
        $this->em()->flush();

        return $invitation;
    }

    public function testAnonymousVisitorSeesInvitation(): void
    {
        $client = static::createClient();
        $this->invitation();

        $client->request('GET', '/invite/'.str_pad('ab', 40, '0'));

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Join Flat');
        self::assertSelectorTextContains('.invitation', 'Olga invited you');
    }

    public function testExpiredInvitation(): void
    {
        $client = static::createClient();
        $this->invitation(created: '-8 days');

        $client->request('GET', '/invite/'.str_pad('ab', 40, '0'));

        self::assertResponseStatusCodeSame(410);
        self::assertSelectorTextContains('h1', "can't be used");
    }

    public function testUnknownToken(): void
    {
        $client = static::createClient();
        $client->request('GET', '/invite/'.str_repeat('f', 40));

        self::assertResponseStatusCodeSame(410);
    }

    public function testSignedInUserAcceptsInvitation(): void
    {
        $client = static::createClient();
        $invitation = $this->invitation();
        $me = $this->createUser('me@example.com', 'Me');
        $this->createHousehold($me, 'Mine');
        $client->loginUser($me);

        $client->request('GET', '/invite/'.str_pad('ab', 40, '0'));
        $client->submitForm('Join Flat');
        $client->followRedirect();

        self::assertSelectorTextContains('.flash', 'Welcome to Flat!');
        self::assertSelectorTextContains('.household-switch option[selected]', 'Flat');
        $membership = static::getContainer()->get(\App\Repository\MembershipRepository::class)->findOneFor($invitation->getHousehold(), $me);
        self::assertSame(HouseholdRole::Editor, $membership?->getRole());
    }

    public function testInvitationWorksOnlyOnce(): void
    {
        $client = static::createClient();
        $this->invitation();
        $first = $this->createUser('first@example.com', 'First');
        $client->loginUser($first);
        $client->request('GET', '/invite/'.str_pad('ab', 40, '0'));
        $client->submitForm('Join Flat');

        $second = $this->createUser('second@example.com', 'Second');
        $client->loginUser($second);
        $client->request('GET', '/invite/'.str_pad('ab', 40, '0'));

        self::assertResponseStatusCodeSame(410);
    }

    public function testNewUserSignsUpAndComesBackToTheInvitation(): void
    {
        $client = static::createClient();
        $this->invitation();

        $client->request('GET', '/invite/'.str_pad('ab', 40, '0'));
        $client->clickLink('Create an account');
        $client->submitForm('Create account', [
            'registration_form[displayName]' => 'Newbie',
            'registration_form[email]' => 'new@example.com',
            'registration_form[plainPassword]' => 'long-enough-1',
        ]);

        self::assertResponseRedirects('http://localhost/invite/'.str_pad('ab', 40, '0'));
        $client->followRedirect();
        $client->submitForm('Join Flat');
        $client->followRedirect();
        self::assertSelectorTextContains('.flash', 'Welcome to Flat!');
    }

    public function testOwnerSeesAndRevokesPendingInvitations(): void
    {
        $client = static::createClient();
        $invitation = $this->invitation();
        $client->loginUser($invitation->getInvitedBy() ?? throw new LogicException());

        $crawler = $client->request('GET', '/household/members');
        self::assertCount(1, $crawler->filter('.pending-invitations li'));
        $client->submit($crawler->filter('.pending-invitations form')->form());
        $client->followRedirect();

        self::assertSelectorTextContains('.flash', 'no longer works');
        $client->request('GET', '/invite/'.str_pad('ab', 40, '0'));
        self::assertResponseStatusCodeSame(410);
    }
}
