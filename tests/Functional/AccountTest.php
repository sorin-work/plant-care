<?php

namespace App\Tests\Functional;

use App\Entity\User;
use App\Tests\Support\Factory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class AccountTest extends WebTestCase
{
    use Factory;

    public function testCreateAndRevokeApiToken(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $this->createHousehold($user);
        $client->loginUser($user);

        $client->request('GET', '/account');
        $crawler = $client->submitForm('Create token');
        $token = $crawler->filter('.token-value')->text();
        self::assertStringStartsWith('pc_', $token);

        $reloaded = $this->em()->find(User::class, $user->getId());
        self::assertSame(hash('sha256', $token), $reloaded?->getApiTokenHash());

        $client->request('GET', '/account');
        self::assertSelectorNotExists('.token-value');
        $client->submitForm('Revoke');
        $client->followRedirect();
        self::assertSelectorTextContains('.flash', 'revoked');
        self::assertNull($this->em()->find(User::class, $user->getId())?->getApiTokenHash());
    }

    public function testCreateAndReplaceCalendarLink(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $this->createHousehold($user);
        $client->loginUser($user);

        $client->request('GET', '/account');
        $client->submitForm('Create calendar link');
        $crawler = $client->followRedirect();
        $first = $crawler->filter('.token-value')->first()->text();
        self::assertMatchesRegularExpression('#/calendar/[a-f0-9]{32}\.ics$#', $first);

        $client->submitForm('Replace calendar link');
        $crawler = $client->followRedirect();
        self::assertNotSame($first, $crawler->filter('.token-value')->first()->text());

        $client->request('GET', parse_url($first, \PHP_URL_PATH));
        self::assertResponseStatusCodeSame(404);
    }

    public function testChangeDisplayName(): void
    {
        $client = static::createClient();
        $user = $this->createUser(name: 'Old');
        $this->createHousehold($user);
        $client->loginUser($user);

        $client->request('GET', '/account');
        $client->submitForm('Save profile', ['profile[displayName]' => 'New Name']);
        $client->followRedirect();

        self::assertSelectorTextContains('.site-nav', 'New Name');
    }

    public function testChangePassword(): void
    {
        $client = static::createClient();
        $hasher = static::getContainer()->get('security.user_password_hasher');
        $user = $this->createUser();
        $user->setPassword($hasher->hashPassword($user, 'old-password'));
        $this->createHousehold($user);
        $client->loginUser($user);

        $client->request('GET', '/account');
        $client->submitForm('Change password', [
            'change_password[current]' => 'old-password',
            'change_password[password][first]' => 'new-password-1',
            'change_password[password][second]' => 'new-password-1',
        ]);
        self::assertResponseRedirects('/account');

        $reloaded = $this->em()->find(User::class, $user->getId());
        self::assertTrue($hasher->isPasswordValid($reloaded, 'new-password-1'));
    }

    public function testWrongCurrentPasswordIsRejected(): void
    {
        $client = static::createClient();
        $hasher = static::getContainer()->get('security.user_password_hasher');
        $user = $this->createUser();
        $user->setPassword($hasher->hashPassword($user, 'old-password'));
        $this->createHousehold($user);
        $client->loginUser($user);

        $client->request('GET', '/account');
        $client->submitForm('Change password', [
            'change_password[current]' => 'guess',
            'change_password[password][first]' => 'new-password-1',
            'change_password[password][second]' => 'new-password-1',
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('form[name=change_password]', 'not your current password');
    }

    public function testOptInToDailyDigest(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $this->createHousehold($user);
        $client->loginUser($user);

        $client->request('GET', '/account');
        $client->submitForm('Save profile', ['profile[digestEnabled]' => true]);

        self::assertTrue($this->em()->find(User::class, $user->getId())?->isDigestEnabled());
    }

    public function testDeleteAccount(): void
    {
        $client = static::createClient();
        $hasher = static::getContainer()->get('security.user_password_hasher');
        $user = $this->createUser('bye@example.com');
        $user->setPassword($hasher->hashPassword($user, 'my-password'));
        $solo = $this->createHousehold($user, 'Solo');
        $soloId = $solo->getId();
        $shared = $this->createHousehold($this->createUser('owner@example.com'), 'Shared');
        $this->addMember($shared, $user, \App\Enum\HouseholdRole::Editor);
        $client->loginUser($user);

        $client->request('GET', '/account');
        $client->submitForm('Delete my account', ['password' => 'my-password']);
        self::assertResponseRedirects('/login');

        $this->em()->clear();
        self::assertNull(static::getContainer()->get(\App\Repository\UserRepository::class)->findOneByEmail('bye@example.com'));
        self::assertNull($this->em()->find(\App\Entity\Household::class, $soloId));
        self::assertNotNull($this->em()->find(\App\Entity\Household::class, $shared->getId()));
    }

    public function testDeleteAccountNeedsPassword(): void
    {
        $client = static::createClient();
        $user = $this->createUser('stay@example.com');
        $this->createHousehold($user);
        $client->loginUser($user);

        $client->request('GET', '/account');
        $client->submitForm('Delete my account', ['password' => 'nope']);
        $client->followRedirect();

        self::assertSelectorTextContains('.flash-error', 'not deleted');
    }

    public function testLastOwnerOfSharedHouseholdCannotDeleteAccount(): void
    {
        $client = static::createClient();
        $hasher = static::getContainer()->get('security.user_password_hasher');
        $user = $this->createUser('owner@example.com');
        $user->setPassword($hasher->hashPassword($user, 'my-password'));
        $household = $this->createHousehold($user, 'Shared');
        $this->addMember($household, $this->createUser('editor@example.com'), \App\Enum\HouseholdRole::Editor);
        $client->loginUser($user);

        $client->request('GET', '/account');
        $client->submitForm('Delete my account', ['password' => 'my-password']);
        $client->followRedirect();

        self::assertSelectorTextContains('.flash-error', 'at least one owner');
        self::assertNotNull(static::getContainer()->get(\App\Repository\UserRepository::class)->findOneByEmail('owner@example.com'));
    }
}
