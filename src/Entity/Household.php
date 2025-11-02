<?php

namespace App\Entity;

use App\Enum\Hemisphere;
use App\Repository\HouseholdRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: HouseholdRepository::class)]
class Household
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 80)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 80)]
    private string $name;

    #[ORM\Column(length: 64)]
    #[Assert\Timezone]
    private string $timezone = 'UTC';

    #[ORM\Column(length: 8, enumType: Hemisphere::class)]
    private Hemisphere $hemisphere = Hemisphere::North;

    #[ORM\Column]
    private DateTimeImmutable $createdAt;

    /** @var Collection<int, Membership> */
    #[ORM\OneToMany(targetEntity: Membership::class, mappedBy: 'household', cascade: ['persist'], orphanRemoval: true)]
    private Collection $memberships;

    public function __construct(string $name)
    {
        $this->name = $name;
        $this->createdAt = new DateTimeImmutable();
        $this->memberships = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getTimezone(): string
    {
        return $this->timezone;
    }

    public function setTimezone(string $timezone): static
    {
        $this->timezone = $timezone;

        return $this;
    }

    public function getHemisphere(): Hemisphere
    {
        return $this->hemisphere;
    }

    public function setHemisphere(Hemisphere $hemisphere): static
    {
        $this->hemisphere = $hemisphere;

        return $this;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * @return Collection<int, Membership>
     */
    public function getMemberships(): Collection
    {
        return $this->memberships;
    }

    public function addMembership(Membership $membership): static
    {
        if (!$this->memberships->contains($membership)) {
            $this->memberships->add($membership);
        }

        return $this;
    }

    public function removeMembership(Membership $membership): static
    {
        $this->memberships->removeElement($membership);

        return $this;
    }
}
