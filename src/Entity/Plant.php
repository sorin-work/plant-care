<?php

namespace App\Entity;

use App\Enum\LightLevel;
use App\Repository\PlantRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PlantRepository::class)]
#[ORM\Index(name: 'idx_plant_household_archived', columns: ['household_id', 'archived_at'])]
class Plant
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Household $household;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Room $room = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Species $species = null;

    #[ORM\Column(length: 80)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 80)]
    private string $name = '';

    #[ORM\Column(length: 20, enumType: LightLevel::class)]
    private LightLevel $light = LightLevel::Medium;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    #[Assert\LessThanOrEqual('today', message: 'The acquisition date cannot be in the future.')]
    private ?DateTimeImmutable $acquiredOn = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(max: 5000)]
    private ?string $notes = null;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $archivedAt = null;

    #[ORM\Column]
    private DateTimeImmutable $createdAt;

    public function __construct(Household $household)
    {
        $this->household = $household;
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getHousehold(): Household
    {
        return $this->household;
    }

    public function getRoom(): ?Room
    {
        return $this->room;
    }

    public function setRoom(?Room $room): static
    {
        if (null !== $room && $room->getHousehold() !== $this->household) {
            throw new InvalidArgumentException('The room belongs to another household.');
        }
        $this->room = $room;

        return $this;
    }

    public function getSpecies(): ?Species
    {
        return $this->species;
    }

    public function setSpecies(?Species $species): static
    {
        $this->species = $species;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = trim($name);

        return $this;
    }

    public function getLight(): LightLevel
    {
        return $this->light;
    }

    public function setLight(LightLevel $light): static
    {
        $this->light = $light;

        return $this;
    }

    public function getAcquiredOn(): ?DateTimeImmutable
    {
        return $this->acquiredOn;
    }

    public function setAcquiredOn(?DateTimeImmutable $acquiredOn): static
    {
        $this->acquiredOn = $acquiredOn;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;

        return $this;
    }

    public function getArchivedAt(): ?DateTimeImmutable
    {
        return $this->archivedAt;
    }

    public function isArchived(): bool
    {
        return null !== $this->archivedAt;
    }

    public function archive(DateTimeImmutable $at = new DateTimeImmutable()): void
    {
        $this->archivedAt ??= $at;
    }

    public function restore(): void
    {
        $this->archivedAt = null;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
