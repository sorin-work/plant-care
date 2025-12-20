<?php

namespace App\Care;

/**
 * Groups due tasks by the room their plant stands in, so a care round can go
 * room by room. Rooms are sorted by name, plants without a room come last.
 * The order of tasks inside a group is kept.
 */
final class TaskGrouper
{
    /**
     * @param list<DueTask> $tasks
     *
     * @return list<RoomGroup>
     */
    public function byRoom(array $tasks): array
    {
        $groups = [];
        foreach ($tasks as $task) {
            $room = $task->plant()->getRoom();
            $key = null === $room ? '' : (string) $room->getId();
            $groups[$key] ??= new RoomGroup($room);
            $groups[$key]->tasks[] = $task;
        }

        usort($groups, static function (RoomGroup $a, RoomGroup $b): int {
            if (null === $a->room || null === $b->room) {
                return (null === $a->room) <=> (null === $b->room);
            }

            return strcasecmp($a->room->getName(), $b->room->getName());
        });

        return $groups;
    }
}
