<?php

namespace App\Entity;

use App\Repository\JournalEntryRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A note (and optionally a photo) about how a plant is doing.
 */
#[ORM\Entity(repositoryClass: JournalEntryRepository::class)]
#[ORM\Index(name: 'idx_journal_plant_date', columns: ['plant_id', 'written_on'])]
class JournalEntry
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Plant $plant;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $author;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    #[Assert\LessThanOrEqual('today', message: 'Journal entries cannot be dated in the future.')]
    private DateTimeImmutable $writtenOn;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'Write a few words about your plant.')]
    #[Assert\Length(max: 5000)]
    private string $note = '';

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $photo = null;

    #[ORM\Column]
    private DateTimeImmutable $createdAt;

    public function __construct(Plant $plant, ?User $author, DateTimeImmutable $writtenOn)
    {
        $this->plant = $plant;
        $this->author = $author;
        $this->writtenOn = $writtenOn->setTime(0, 0);
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

    public function getAuthor(): ?User
    {
        return $this->author;
    }

    public function getWrittenOn(): DateTimeImmutable
    {
        return $this->writtenOn;
    }

    public function setWrittenOn(DateTimeImmutable $writtenOn): static
    {
        $this->writtenOn = $writtenOn->setTime(0, 0);

        return $this;
    }

    public function getNote(): string
    {
        return $this->note;
    }

    public function setNote(string $note): static
    {
        $this->note = trim($note);

        return $this;
    }

    public function getPhoto(): ?string
    {
        return $this->photo;
    }

    public function setPhoto(?string $photo): static
    {
        $this->photo = $photo;

        return $this;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
