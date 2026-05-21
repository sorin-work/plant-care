<?php

namespace App\Entity;

use App\Enum\HouseholdRole;
use App\Repository\InvitationRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

/**
 * A link that lets someone join a household, also before they have an account.
 */
#[ORM\Entity(repositoryClass: InvitationRepository::class)]
class Invitation
{
    public const VALID_DAYS = 7;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Household $household;

    #[ORM\Column(length: 16, enumType: HouseholdRole::class)]
    private HouseholdRole $role;

    #[ORM\Column(length: 64, unique: true)]
    private string $tokenHash;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $invitedBy;

    #[ORM\Column]
    private DateTimeImmutable $expiresAt;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $acceptedAt = null;

    #[ORM\Column]
    private DateTimeImmutable $createdAt;

    /**
     * @param string $token the secret part of the link; only its hash is stored
     */
    public function __construct(Household $household, HouseholdRole $role, string $token, ?User $invitedBy, DateTimeImmutable $now = new DateTimeImmutable())
    {
        $this->household = $household;
        $this->role = $role;
        $this->tokenHash = self::hash($token);
        $this->invitedBy = $invitedBy;
        $this->createdAt = $now;
        $this->expiresAt = $now->modify(\sprintf('+%d days', self::VALID_DAYS));
    }

    public static function hash(string $token): string
    {
        return hash('sha256', $token);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getHousehold(): Household
    {
        return $this->household;
    }

    public function getRole(): HouseholdRole
    {
        return $this->role;
    }

    public function getInvitedBy(): ?User
    {
        return $this->invitedBy;
    }

    public function getExpiresAt(): DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function getAcceptedAt(): ?DateTimeImmutable
    {
        return $this->acceptedAt;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function isUsable(DateTimeImmutable $now): bool
    {
        return null === $this->acceptedAt && $now < $this->expiresAt;
    }

    public function markAccepted(DateTimeImmutable $now): void
    {
        $this->acceptedAt = $now;
    }
}
