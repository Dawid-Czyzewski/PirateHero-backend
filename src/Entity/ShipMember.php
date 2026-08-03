<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\ShipRole;
use App\Repository\ShipMemberRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;
use Symfony\Component\Serializer\Annotation\SerializedName;

#[ORM\Entity(repositoryClass: ShipMemberRepository::class)]
#[ORM\Table(name: 'ship_member')]
#[ORM\UniqueConstraint(name: 'unique_user_ship', columns: ['user_id', 'ship_id'])]
class ShipMember
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['ship:read', 'user:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'shipMembers')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['ship:read'])]
    #[MaxDepth(1)]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'members')]
    #[ORM\JoinColumn(name: 'ship_id', referencedColumnName: 'id', nullable: false)]
    private ?Ship $ship = null;

    #[ORM\Column(length: 20, enumType: ShipRole::class)]
    #[Groups(['ship:read', 'user:read'])]
    private ?ShipRole $role = null;

    #[ORM\Column]
    #[Groups(['ship:read', 'user:read'])]
    private ?\DateTimeImmutable $joinedAt = null;

    #[ORM\Column(options: ['default' => 0])]
    #[Groups(['ship:read', 'user:read'])]
    private int $goldContributed = 0;

    #[ORM\Column(name: 'diamonds_contributed', options: ['default' => 0])]
    #[Groups(['ship:read', 'user:read'])]
    #[SerializedName('diamondsContributed')]
    private int $diamondsContributed = 0;

    public function __construct()
    {
        $this->joinedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getShip(): ?Ship
    {
        return $this->ship;
    }

    public function setShip(?Ship $ship): static
    {
        $this->ship = $ship;

        return $this;
    }

    public function getRole(): ?ShipRole
    {
        return $this->role;
    }

    public function setRole(ShipRole $role): static
    {
        $this->role = $role;

        return $this;
    }

    public function isOwner(): bool
    {
        return $this->role === ShipRole::OWNER;
    }

    public function isManager(): bool
    {
        return $this->role === ShipRole::MANAGER;
    }

    public function isMember(): bool
    {
        return $this->role === ShipRole::MEMBER;
    }

    public function getJoinedAt(): ?\DateTimeImmutable
    {
        return $this->joinedAt;
    }

    public function setJoinedAt(\DateTimeImmutable $joinedAt): static
    {
        $this->joinedAt = $joinedAt;

        return $this;
    }

    public function getGoldContributed(): int
    {
        return $this->goldContributed;
    }

    public function addGoldContribution(int $amount): static
    {
        $this->goldContributed += $amount;

        return $this;
    }

    public function getDiamondsContributed(): int
    {
        return $this->diamondsContributed;
    }

    public function addDiamondsContribution(int $amount): static
    {
        $this->diamondsContributed += $amount;

        return $this;
    }
}
