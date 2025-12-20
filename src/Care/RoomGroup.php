<?php

namespace App\Care;

use App\Entity\Room;

final class RoomGroup
{
    /** @var list<DueTask> */
    public array $tasks = [];

    public function __construct(public readonly ?Room $room)
    {
    }

    public function label(): string
    {
        return $this->room?->getName() ?? 'No room';
    }
}
