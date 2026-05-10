<?php

namespace App\Tests\Functional;

use App\Entity\Room;
use App\Repository\RoomRepository;
use App\Tests\Support\Factory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class RoomControllerTest extends WebTestCase
{
    use Factory;

    public function testEmptyState(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $this->createHousehold($user);
        $client->loginUser($user);

        $client->request('GET', '/rooms');

        self::assertSelectorTextContains('.empty', 'No rooms yet');
    }

    public function testAddRoom(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $household = $this->createHousehold($user);
        $client->loginUser($user);

        $client->request('GET', '/rooms');
        $client->submitForm('Add room', ['room[name]' => ' Kitchen ']);
        self::assertResponseRedirects('/rooms');
        $client->followRedirect();

        self::assertSelectorTextContains('.list', 'Kitchen');
        self::assertCount(1, static::getContainer()->get(RoomRepository::class)->findForHousehold($household));
    }

    public function testDuplicateRoomNameIsRejected(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $household = $this->createHousehold($user);
        $this->em()->persist(new Room($household, 'Kitchen'));
        $this->em()->flush();
        $client->loginUser($user);

        $client->request('GET', '/rooms');
        $client->submitForm('Add room', ['room[name]' => 'Kitchen']);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('section.card form', 'already have a room');
    }

    public function testRenameAndDeleteRoom(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $household = $this->createHousehold($user);
        $room = new Room($household, 'Bathrom');
        $this->em()->persist($room);
        $this->em()->flush();
        $client->loginUser($user);

        $client->request('GET', '/rooms/'.$room->getId().'/edit');
        $client->submitForm('Save', ['room[name]' => 'Bathroom']);
        $crawler = $client->followRedirect();
        self::assertSelectorTextContains('.list', 'Bathroom');

        $client->submit($crawler->filter('.list form')->form());
        $client->followRedirect();
        self::assertSelectorTextContains('.empty', 'No rooms yet');
    }

    public function testCannotEditRoomOfAnotherHousehold(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $this->createHousehold($user);
        $other = $this->createHousehold($this->createUser('other@example.com'), 'Other');
        $room = new Room($other, 'Their kitchen');
        $this->em()->persist($room);
        $this->em()->flush();
        $client->loginUser($user);

        $client->request('GET', '/rooms/'.$room->getId().'/edit');

        self::assertResponseStatusCodeSame(404);
    }

    public function testShowsPlantCounts(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $household = $this->createHousehold($user);
        $kitchen = $this->createRoom($household, 'Kitchen');
        $this->createRoom($household, 'Hall');
        $this->createPlant($household, 'Basil', $kitchen);
        $this->createPlant($household, 'Mint', $kitchen);
        $archived = $this->createPlant($household, 'Old', $kitchen);
        $archived->archive();
        $this->em()->flush();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/rooms');

        $rows = $crawler->filter('.list li')->each(fn ($li) => $li->filter('span')->first()->text());
        self::assertSame(['Hall 0 plants', 'Kitchen 2 plants'], $rows);
    }

    public function testRoomLightIsOfferedToPlantForm(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $this->createHousehold($user);
        $client->loginUser($user);

        $client->request('GET', '/rooms');
        $client->submitForm('Add room', ['room[name]' => 'Sunroom', 'room[light]' => 'direct']);
        $crawler = $client->followRedirect();
        self::assertSelectorTextContains('.list', 'direct sun');

        $crawler = $client->request('GET', '/plants/new');
        self::assertSame('direct', $crawler->filter('#plant_room option:contains("Sunroom")')->attr('data-light'));
    }
}
