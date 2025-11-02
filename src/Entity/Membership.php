<?php

namespace App\Entity;

use App\Enum\HouseholdRole;
use App\Repository\MembershipRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MembershipRepository::class)]
#[ORM\UniqueConstraint(name: 'uniq_membership_household_user', columns: ['household_id', 'user_id'])]
class Membership
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'memberships')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Household $household;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(length: 16, enumType: HouseholdRole::class)]
    private HouseholdRole $role;

    #[ORM\Column]
    private DateTimeImmutable $joinedAt;

    public function __construct(Household $household, User $user, HouseholdRole $role)
    {
        $this->household = $household;
        $this->user = $user;
        $this->role = $role;
        $this->joinedAt = new DateTimeImmutable();
        $household->addMembership($this);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getHousehold(): Household
    {
        return $this->household;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getRole(): HouseholdRole
    {
        return $this->role;
    }

    public function setRole(HouseholdRole $role): static
    {
        $this->role = $role;

        return $this;
    }

    public function getJoinedAt(): DateTimeImmutable
    {
        return $this->joinedAt;
    }
}
