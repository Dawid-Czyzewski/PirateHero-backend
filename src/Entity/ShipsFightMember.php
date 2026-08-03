<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ORM\Table(name: 'ships_fight_member')]
class ShipsFightMember
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'fightMembers')]
    #[ORM\JoinColumn(name: 'ships_fight_id', referencedColumnName: 'id', nullable: false)]
    private ?ShipsFight $shipsFight = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['ship:read'])]
    private ?User $user = null;

    #[ORM\Column]
    #[Groups(['ship:read'])]
    private bool $isDefeated = false;

    #[ORM\Column]
    #[Groups(['ship:read'])]
    private int $initialHealth = 0;

    #[ORM\Column]
    #[Groups(['ship:read'])]
    private int $currentHealth = 0;

    #[ORM\Column]
    #[Groups(['ship:read'])]
    private bool $isAttackerSide = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getShipsFight(): ?ShipsFight
    {
        return $this->shipsFight;
    }

    public function setShipsFight(?ShipsFight $shipsFight): static
    {
        $this->shipsFight = $shipsFight;

        return $this;
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

    public function isDefeated(): bool
    {
        return $this->isDefeated;
    }

    public function setIsDefeated(bool $isDefeated): static
    {
        $this->isDefeated = $isDefeated;

        return $this;
    }

    public function getInitialHealth(): int
    {
        return $this->initialHealth;
    }

    public function setInitialHealth(int $initialHealth): static
    {
        $this->initialHealth = $initialHealth;

        return $this;
    }

    public function getCurrentHealth(): int
    {
        return $this->currentHealth;
    }

    public function setCurrentHealth(int $currentHealth): static
    {
        $this->currentHealth = $currentHealth;

        return $this;
    }

    public function isAttackerSide(): bool
    {
        return $this->isAttackerSide;
    }

    public function setIsAttackerSide(bool $isAttackerSide): static
    {
        $this->isAttackerSide = $isAttackerSide;

        return $this;
    }
}
