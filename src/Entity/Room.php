<?php

namespace App\Entity;

use App\Enum\LightLevel;
use App\Repository\RoomRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: RoomRepository::class)]
#[ORM\UniqueConstraint(name: 'uniq_room_household_name', columns: ['household_id', 'name'])]
#[UniqueEntity(fields: ['household', 'name'], message: 'You already have a room with this name.', errorPath: 'name')]
class Room
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Household $household;

    #[ORM\Column(length: 60)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 60)]
    private string $name;

    /** Typical light in this room, used as the default for new plants. */
    #[ORM\Column(length: 20, nullable: true, enumType: LightLevel::class)]
    private ?LightLevel $light = null;

    public function __construct(Household $household, string $name = '')
    {
        $this->household = $household;
        $this->name = $name;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getHousehold(): Household
    {
        return $this->household;
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

    public function __toString(): string
    {
        return $this->name;
    }

    public function getLight(): ?LightLevel
    {
        return $this->light;
    }

    public function setLight(?LightLevel $light): static
    {
        $this->light = $light;

        return $this;
    }
}
