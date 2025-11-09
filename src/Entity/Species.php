<?php

namespace App\Entity;

use App\Enum\LightLevel;
use App\Repository\SpeciesRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A catalog entry with sensible care defaults for a kind of plant.
 */
#[ORM\Entity(repositoryClass: SpeciesRepository::class)]
#[ORM\UniqueConstraint(name: 'uniq_species_scientific_name', columns: ['scientific_name'])]
class Species
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 120)]
    #[Assert\NotBlank]
    private string $commonName;

    #[ORM\Column(length: 120)]
    #[Assert\NotBlank]
    private string $scientificName;

    #[ORM\Column]
    #[Assert\Range(min: 1, max: 90)]
    private int $waterIntervalDays;

    #[ORM\Column(nullable: true)]
    #[Assert\Range(min: 7, max: 365)]
    private ?int $fertilizeIntervalDays = null;

    #[ORM\Column]
    #[Assert\Range(min: 6, max: 60)]
    private int $repotIntervalMonths = 18;

    #[ORM\Column(length: 20, enumType: LightLevel::class)]
    private LightLevel $light;

    #[ORM\Column]
    private bool $toxicToPets = false;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $careNotes = null;

    public function __construct(string $commonName, string $scientificName, int $waterIntervalDays, LightLevel $light)
    {
        $this->commonName = $commonName;
        $this->scientificName = $scientificName;
        $this->waterIntervalDays = $waterIntervalDays;
        $this->light = $light;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCommonName(): string
    {
        return $this->commonName;
    }

    public function setCommonName(string $commonName): static
    {
        $this->commonName = $commonName;

        return $this;
    }

    public function getScientificName(): string
    {
        return $this->scientificName;
    }

    public function getWaterIntervalDays(): int
    {
        return $this->waterIntervalDays;
    }

    public function setWaterIntervalDays(int $days): static
    {
        $this->waterIntervalDays = $days;

        return $this;
    }

    public function getFertilizeIntervalDays(): ?int
    {
        return $this->fertilizeIntervalDays;
    }

    public function setFertilizeIntervalDays(?int $days): static
    {
        $this->fertilizeIntervalDays = $days;

        return $this;
    }

    public function getRepotIntervalMonths(): int
    {
        return $this->repotIntervalMonths;
    }

    public function setRepotIntervalMonths(int $months): static
    {
        $this->repotIntervalMonths = $months;

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

    public function isToxicToPets(): bool
    {
        return $this->toxicToPets;
    }

    public function setToxicToPets(bool $toxic): static
    {
        $this->toxicToPets = $toxic;

        return $this;
    }

    public function getCareNotes(): ?string
    {
        return $this->careNotes;
    }

    public function setCareNotes(?string $notes): static
    {
        $this->careNotes = $notes;

        return $this;
    }

    public function __toString(): string
    {
        return \sprintf('%s (%s)', $this->commonName, $this->scientificName);
    }
}
