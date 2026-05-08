<?php

namespace App\Tests\Functional;

use App\Entity\JournalEntry;
use App\Enum\HouseholdRole;
use App\Tests\Support\Factory;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class PhotoTest extends WebTestCase
{
    use Factory;

    private function leaf(): UploadedFile
    {
        $tmp = tempnam(sys_get_temp_dir(), 'leaf');
        copy(__DIR__.'/../fixtures/leaf.png', $tmp);

        return new UploadedFile($tmp, 'leaf.png', 'image/png', null, true);
    }

    private function document(): UploadedFile
    {
        $tmp = tempnam(sys_get_temp_dir(), 'doc');
        file_put_contents($tmp, '%PDF-1.4 not a photo');

        return new UploadedFile($tmp, 'receipt.pdf', 'application/pdf', null, true);
    }

    public function testUploadPhotoWithJournalEntry(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $plant = $this->createPlant($this->createHousehold($user), 'Monty');
        $client->loginUser($user);

        $crawler = $client->request('GET', '/plants/'.$plant->getId());
        $form = $crawler->selectButton('Add to journal')->form(['journal_entry[note]' => 'Look at this leaf']);
        $client->request('POST', $form->getUri(), $form->getPhpValues(), ['journal_entry' => ['photoFile' => $this->leaf()]]);
        $crawler = $client->followRedirect();

        $src = $crawler->filter('.journal-photo')->attr('src');
        self::assertMatchesRegularExpression('#^/photos/[a-f0-9]{32}\.png$#', (string) $src);

        $client->request('GET', (string) $src);
        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('content-type', 'image/png');
    }

    public function testRejectsNonImageUpload(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $plant = $this->createPlant($this->createHousehold($user), 'Monty');
        $client->loginUser($user);

        $crawler = $client->request('GET', '/plants/'.$plant->getId().'/journal/new');
        $form = $crawler->selectButton('Add to journal')->form(['journal_entry[note]' => 'Receipt']);
        $client->request('POST', $form->getUri(), $form->getPhpValues(), ['journal_entry' => ['photoFile' => $this->document()]]);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('form', 'JPEG, PNG or WebP');
    }

    public function testPhotoIsHiddenFromOtherHouseholds(): void
    {
        $client = static::createClient();
        $owner = $this->createUser('owner@example.com');
        $plant = $this->createPlant($this->createHousehold($owner), 'Monty');
        $name = static::getContainer()->get(\App\Photo\PhotoStorage::class)->store($this->leaf());
        $entry = (new JournalEntry($plant, $owner, new DateTimeImmutable()))->setNote('Private')->setPhoto($name);
        $this->em()->persist($entry);
        $this->em()->flush();

        $stranger = $this->createUser('stranger@example.com');
        $this->createHousehold($stranger);
        $client->loginUser($stranger);
        $client->request('GET', '/photos/'.$name);
        self::assertResponseStatusCodeSame(404);
    }

    public function testPhotoIsVisibleToOtherMembers(): void
    {
        $client = static::createClient();
        $owner = $this->createUser('owner@example.com');
        $household = $this->createHousehold($owner);
        $plant = $this->createPlant($household, 'Monty');
        $name = static::getContainer()->get(\App\Photo\PhotoStorage::class)->store($this->leaf());
        $this->em()->persist((new JournalEntry($plant, $owner, new DateTimeImmutable()))->setNote('Shared')->setPhoto($name));
        $this->em()->flush();

        $member = $this->createUser('member@example.com');
        $this->addMember($household, $member, HouseholdRole::Viewer);
        $client->loginUser($member);
        $client->request('GET', '/photos/'.$name);

        self::assertResponseIsSuccessful();
    }

    public function testUnknownPhotoIs404(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $this->createHousehold($user);
        $client->loginUser($user);

        $client->request('GET', '/photos/'.str_repeat('a', 32).'.jpg');

        self::assertResponseStatusCodeSame(404);
    }

    public function testUploadCoverPhotoWhenEditingPlant(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $plant = $this->createPlant($this->createHousehold($user), 'Monty');
        $client->loginUser($user);

        $crawler = $client->request('GET', '/plants/'.$plant->getId().'/edit');
        $form = $crawler->selectButton('Save')->form();
        $client->request('POST', $form->getUri(), $form->getPhpValues(), ['plant' => ['coverFile' => $this->leaf()]]);
        $client->followRedirect();
        self::assertSelectorExists('.plant-cover-large');

        $crawler = $client->request('GET', '/plants');
        $src = (string) $crawler->filter('img.plant-cover')->attr('src');
        $client->request('GET', $src);
        self::assertResponseIsSuccessful();
    }

    public function testGalleryShowsPhotosOldestFirst(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $plant = $this->createPlant($this->createHousehold($user), 'Monty');
        $storage = static::getContainer()->get(\App\Photo\PhotoStorage::class);
        $this->em()->persist((new JournalEntry($plant, $user, new DateTimeImmutable('2026-03-01')))->setNote('Later')->setPhoto($storage->store($this->leaf())));
        $this->em()->persist((new JournalEntry($plant, $user, new DateTimeImmutable('2026-01-01')))->setNote('Earlier')->setPhoto($storage->store($this->leaf())));
        $this->em()->persist((new JournalEntry($plant, $user, new DateTimeImmutable('2026-02-01')))->setNote('No photo'));
        $this->em()->flush();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/plants/'.$plant->getId().'/photos');

        self::assertSame(['1 Jan 2026', '1 Mar 2026'], $crawler->filter('.gallery li span')->each(fn ($s) => $s->text()));
    }

    public function testPhotosAreServedWithSafeHeaders(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $plant = $this->createPlant($this->createHousehold($user), 'Monty');
        $name = static::getContainer()->get(\App\Photo\PhotoStorage::class)->store($this->leaf());
        $this->em()->persist((new JournalEntry($plant, $user, new DateTimeImmutable()))->setNote('x')->setPhoto($name));
        $this->em()->flush();
        $client->loginUser($user);

        $client->request('GET', '/photos/'.$name);

        self::assertResponseHeaderSame('content-type', 'image/png');
        self::assertResponseHeaderSame('x-content-type-options', 'nosniff');
    }

    public function testHtmlUploadedAsPngIsRejected(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $plant = $this->createPlant($this->createHousehold($user), 'Monty');
        $client->loginUser($user);

        $tmp = tempnam(sys_get_temp_dir(), 'evil');
        file_put_contents($tmp, '<html><body><script>alert(1)</script></body></html>');
        $crawler = $client->request('GET', '/plants/'.$plant->getId().'/journal/new');
        $form = $crawler->selectButton('Add to journal')->form(['journal_entry[note]' => 'Nice leaf']);
        $client->request('POST', $form->getUri(), $form->getPhpValues(), ['journal_entry' => ['photoFile' => new UploadedFile($tmp, 'leaf.png', 'image/png', null, true)]]);

        self::assertResponseStatusCodeSame(422);
        self::assertCount(0, $this->em()->getRepository(JournalEntry::class)->findAll());
    }
}
