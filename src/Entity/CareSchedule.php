<?php

namespace App\Entity;

use App\Enum\CareTaskType;
use App\Repository\CareScheduleRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * How often one kind of care is needed for a plant, and when it is due next.
 */
#[ORM\Entity(repositoryClass: CareScheduleRepository::class)]
#[ORM\UniqueConstraint(name: 'uniq_schedule_plant_type', columns: ['plant_id', 'type'])]
#[ORM\Index(name: 'idx_schedule_next_due', columns: ['next_due_on'])]
class CareSchedule
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'schedules')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Plant $plant;

    #[ORM\Column(length: 16, enumType: CareTaskType::class)]
    private CareTaskType $type;

    /** Base interval in days, before seasonal adjustment. */
    #[ORM\Column]
    #[Assert\Range(min: 1, max: 1825)]
    private int $intervalDays;

    #[ORM\Column]
    private bool $seasonal = true;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $lastDoneOn = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private DateTimeImmutable $nextDueOn;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $snoozedUntil = null;

    /** How to do it, e.g. "bottom water, 300 ml" or "half strength". */
    #[ORM\Column(length: 160, nullable: true)]
    #[Assert\Length(max: 160)]
    private ?string $notes = null;

    public function __construct(Plant $plant, CareTaskType $type, int $intervalDays, DateTimeImmutable $nextDueOn)
    {
        $this->plant = $plant;
        $this->type = $type;
        $this->intervalDays = $intervalDays;
        $this->nextDueOn = $nextDueOn;
        $plant->addSchedule($this);
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

    public function getIntervalDays(): int
    {
        return $this->intervalDays;
    }

    public function setIntervalDays(int $intervalDays): static
    {
        $this->intervalDays = $intervalDays;

        return $this;
    }

    public function isSeasonal(): bool
    {
        return $this->seasonal;
    }

    public function setSeasonal(bool $seasonal): static
    {
        $this->seasonal = $seasonal;

        return $this;
    }

    public function getLastDoneOn(): ?DateTimeImmutable
    {
        return $this->lastDoneOn;
    }

    public function setLastDoneOn(?DateTimeImmutable $lastDoneOn): static
    {
        $this->lastDoneOn = $lastDoneOn;

        return $this;
    }

    public function getNextDueOn(): DateTimeImmutable
    {
        return $this->nextDueOn;
    }

    public function setNextDueOn(DateTimeImmutable $nextDueOn): static
    {
        $this->nextDueOn = $nextDueOn;

        return $this;
    }

    public function getSnoozedUntil(): ?DateTimeImmutable
    {
        return $this->snoozedUntil;
    }

    public function setSnoozedUntil(?DateTimeImmutable $snoozedUntil): static
    {
        $this->snoozedUntil = $snoozedUntil;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $notes = null === $notes ? null : trim($notes);
        $this->notes = '' === $notes ? null : $notes;

        return $this;
    }
}
