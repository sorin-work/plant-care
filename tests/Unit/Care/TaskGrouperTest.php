<?php

namespace App\Tests\Unit\Care;

use App\Care\DueTask;
use App\Care\RoomGroup;
use App\Care\TaskGrouper;
use App\Entity\CareSchedule;
use App\Entity\Household;
use App\Entity\Plant;
use App\Entity\Room;
use App\Enum\CareTaskType;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

final class TaskGrouperTest extends TestCase
{
    private Household $household;

    protected function setUp(): void
    {
        $this->household = new Household('Home');
    }

    private function task(string $plant, ?Room $room, int $overdue = 0): DueTask
    {
        $p = (new Plant($this->household))->setName($plant)->setRoom($room);

        return new DueTask(new CareSchedule($p, CareTaskType::Water, 7, new DateTimeImmutable()), $overdue);
    }

    private function room(string $name, int $id): Room
    {
        $room = new Room($this->household, $name);
        (new ReflectionProperty(Room::class, 'id'))->setValue($room, $id);

        return $room;
    }

    public function testGroupsByRoomSortedByNameWithNoRoomLast(): void
    {
        $living = $this->room('Living room', 1);
        $bath = $this->room('bathroom', 2);

        $groups = (new TaskGrouper())->byRoom([
            $this->task('A', null),
            $this->task('B', $living),
            $this->task('C', $bath),
            $this->task('D', $living),
        ]);

        self::assertSame(['bathroom', 'Living room', 'No room'], array_map(fn (RoomGroup $g) => $g->label(), $groups));
        self::assertSame(['B', 'D'], array_map(fn (DueTask $t) => $t->plant()->getName(), $groups[1]->tasks));
    }

    public function testKeepsTaskOrderInsideGroup(): void
    {
        $room = $this->room('Kitchen', 1);

        $groups = (new TaskGrouper())->byRoom([
            $this->task('Late', $room, 5),
            $this->task('Today', $room, 0),
        ]);

        self::assertSame(['Late', 'Today'], array_map(fn (DueTask $t) => $t->plant()->getName(), $groups[0]->tasks));
    }

    public function testEmptyInput(): void
    {
        self::assertSame([], (new TaskGrouper())->byRoom([]));
    }
}
