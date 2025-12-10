<?php

namespace App\Entity;

use App\Enum\CareAction;
use App\Enum\CareTaskType;
use App\Repository\CareEventRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Log entry for something that happened to a care task.
 */
#[ORM\Entity(repositoryClass: CareEventRepository::class)]
#[ORM\Index(name: 'idx_care_event_plant_date', columns: ['plant_id', 'happened_on'])]
class CareEvent
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Plant $plant;

    #[ORM\Column(length: 16, enumType: CareTaskType::class)]
    private CareTaskType $type;

    #[ORM\Column(length: 16, enumType: CareAction::class)]
    private CareAction $action;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private DateTimeImmutable $happenedOn;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $user;

    #[ORM\Column]
    private DateTimeImmutable $createdAt;

    public function __construct(Plant $plant, CareTaskType $type, CareAction $action, DateTimeImmutable $happenedOn, ?User $user = null)
    {
        $this->plant = $plant;
        $this->type = $type;
        $this->action = $action;
        $this->happenedOn = $happenedOn->setTime(0, 0);
        $this->user = $user;
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPlant(): Plant
    {
        return $this->plant;
    }

    public function getType(): CareTaskType
    {
        return $this->type;
    }

    public function getAction(): CareAction
    {
        return $this->action;
    }

    public function getHappenedOn(): DateTimeImmutable
    {
        return $this->happenedOn;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
