<?php

namespace App\Entity;

use App\Repository\JournalEntryRepository;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Clock\Clock;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * A note (and optionally a photo) about how a plant is doing.
 */
#[ORM\Entity(repositoryClass: JournalEntryRepository::class)]
#[ORM\Index(name: 'idx_journal_plant_date', columns: ['plant_id', 'written_on'])]
#[ORM\Index(name: 'idx_journal_photo', columns: ['photo'])]
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

    /**
     * "In the future" is judged by the household's calendar, not the server's:
     * in Auckland it can already be tomorrow in UTC terms.
     */
    #[Assert\Callback]
    public function validateNotInFuture(ExecutionContextInterface $context): void
    {
        $date = $this->getWrittenOn();
        $today = Clock::get()->now()->setTimezone(new DateTimeZone($this->plant->getHousehold()->getTimezone()))->format('Y-m-d');
        if ($date->format('Y-m-d') > $today) {
            $context->buildViolation('Journal entries cannot be dated in the future.')->atPath('writtenOn')->addViolation();
        }
    }
}
