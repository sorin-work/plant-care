<?php

namespace App\Tests\Functional;

use App\Entity\JournalEntry;
use App\Enum\HouseholdRole;
use App\Tests\Support\Factory;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class JournalControllerTest extends WebTestCase
{
    use Factory;

    public function testAddJournalEntryFromPlantPage(): void
    {
        $client = static::createClient();
        $user = $this->createUser(name: 'Mara');
        $plant = $this->createPlant($this->createHousehold($user), 'Monty');
        $client->loginUser($user);

        $client->request('GET', '/plants/'.$plant->getId());
        $client->submitForm('Add to journal', [
            'journal_entry[note]' => "New leaf!\nMoved it closer to the window.",
            'journal_entry[writtenOn]' => '2026-01-10',
        ]);
        self::assertResponseRedirects('/plants/'.$plant->getId());
        $client->followRedirect();

        self::assertSelectorTextContains('.journal-entry', 'New leaf!');
        self::assertSelectorTextContains('.journal-entry header', '10 Jan 2026');
        self::assertSelectorTextContains('.journal-entry header', 'Mara');
        self::assertStringContainsString('New leaf!<br />', (string) $client->getResponse()->getContent());
    }

    public function testEmptyNoteIsRejected(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $plant = $this->createPlant($this->createHousehold($user));
        $client->loginUser($user);

        $client->request('GET', '/plants/'.$plant->getId());
        $client->submitForm('Add to journal', ['journal_entry[note]' => '   ']);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('form', 'Write a few words');
    }

    public function testFutureDateIsRejected(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $plant = $this->createPlant($this->createHousehold($user));
        $client->loginUser($user);

        $client->request('GET', '/plants/'.$plant->getId().'/journal/new');
        $client->submitForm('Add to journal', [
            'journal_entry[note]' => 'Hello from the future',
            'journal_entry[writtenOn]' => (new DateTimeImmutable('+3 days'))->format('Y-m-d'),
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertCount(0, $this->em()->getRepository(JournalEntry::class)->findAll());
    }

    public function testCannotWriteInForeignPlantJournal(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $this->createHousehold($user);
        $plant = $this->createPlant($this->createHousehold($this->createUser('x@example.com')));
        $client->loginUser($user);

        $client->request('GET', '/plants/'.$plant->getId().'/journal/new');

        self::assertResponseStatusCodeSame(404);
    }

    public function testLinksInNotesAreClickable(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $plant = $this->createPlant($this->createHousehold($user));
        $client->loginUser($user);

        $client->request('GET', '/plants/'.$plant->getId());
        $client->submitForm('Add to journal', ['journal_entry[note]' => 'Bought at https://plants.example/monstera']);
        $client->followRedirect();

        self::assertSelectorExists('.journal-entry a[href="https://plants.example/monstera"]');
    }

    private function addEntries(\App\Entity\Plant $plant, int $count): void
    {
        for ($i = 0; $i < $count; ++$i) {
            $entry = (new JournalEntry($plant, null, new DateTimeImmutable('2026-01-01 +'.$i.' days')))->setNote('Entry '.($i + 1));
            $this->em()->persist($entry);
        }
        $this->em()->flush();
    }

    public function testJournalPageShowsNewestFirst(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $plant = $this->createPlant($this->createHousehold($user));
        $this->addEntries($plant, 3);
        $client->loginUser($user);

        $crawler = $client->request('GET', '/plants/'.$plant->getId().'/journal');

        self::assertSame(['Entry 3', 'Entry 2', 'Entry 1'], $crawler->filter('.journal-entry p')->each(fn ($p) => $p->text()));
        self::assertSelectorNotExists('.pager');
    }

    public function testJournalIsPaginated(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $plant = $this->createPlant($this->createHousehold($user));
        $this->addEntries($plant, 20);
        $client->loginUser($user);

        $crawler = $client->request('GET', '/plants/'.$plant->getId().'/journal');
        self::assertCount(10, $crawler->filter('.journal-entry'));
        self::assertSelectorTextContains('.pager', 'Page 1 of 2');

        $crawler = $client->clickLink('Older →');
        self::assertCount(10, $crawler->filter('.journal-entry'));
        self::assertSame('Entry 1', $crawler->filter('.journal-entry p')->last()->text());
        self::assertSelectorTextContains('.pager', 'Newer');
    }

    public function testAuthorCanDeleteEntry(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $plant = $this->createPlant($this->createHousehold($user));
        $this->em()->persist((new JournalEntry($plant, $user, new DateTimeImmutable()))->setNote('Oops'));
        $this->em()->flush();
        $client->loginUser($user);

        $client->request('GET', '/plants/'.$plant->getId().'/journal');
        $client->submitForm('Delete');
        $client->followRedirect();

        self::assertSelectorTextContains('.flash', 'Journal entry deleted.');
        self::assertCount(0, $this->em()->getRepository(JournalEntry::class)->findAll());
    }

    public function testOtherMemberCannotDeleteEntry(): void
    {
        $client = static::createClient();
        $owner = $this->createUser('owner@example.com');
        $household = $this->createHousehold($owner);
        $plant = $this->createPlant($household);
        $entry = (new JournalEntry($plant, $owner, new DateTimeImmutable()))->setNote('Mine');
        $this->em()->persist($entry);
        $this->em()->flush();
        $editor = $this->createUser('editor@example.com');
        $this->addMember($household, $editor, HouseholdRole::Editor);
        $client->loginUser($editor);

        $crawler = $client->request('GET', '/plants/'.$plant->getId().'/journal');
        self::assertCount(0, $crawler->filter('.entry-delete'));

        $client->request('POST', '/journal/'.$entry->getId().'/delete', ['_token' => 'x']);
        self::assertResponseStatusCodeSame(403);
    }

    public function testHtmlInNotesIsEscaped(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $plant = $this->createPlant($this->createHousehold($user));
        $client->loginUser($user);

        $client->request('GET', '/plants/'.$plant->getId());
        $client->submitForm('Add to journal', ['journal_entry[note]' => "<img src=x onerror=alert(1)>\nsee https://example.com"]);
        $crawler = $client->followRedirect();

        self::assertCount(0, $crawler->filter('.journal-entry img'));
        self::assertStringContainsString('&lt;img src=x onerror=alert(1)&gt;<br />', (string) $client->getResponse()->getContent());
        self::assertSelectorExists('.journal-entry a[href="https://example.com"]');
    }

    public function testLastPartialPageIsReachable(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $plant = $this->createPlant($this->createHousehold($user));
        $this->addEntries($plant, 25);
        $client->loginUser($user);

        $client->request('GET', '/plants/'.$plant->getId().'/journal?page=2');
        self::assertSelectorTextContains('.pager', 'Page 2 of 3');

        $crawler = $client->clickLink('Older →');
        self::assertCount(5, $crawler->filter('.journal-entry'));
        self::assertSame('Entry 1', $crawler->filter('.journal-entry p')->last()->text());
    }
}
