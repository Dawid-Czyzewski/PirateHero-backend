<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ShipFightNotificationRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ShipFightNotificationRepository::class)]
#[ORM\Table(name: 'ship_fight_notification')]
class ShipFightNotification
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['ship:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['ship:read'])]
    private ?User $user = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'ship_id', referencedColumnName: 'id', nullable: false)]
    private ?Ship $ship = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'attacker_ship_id', referencedColumnName: 'id', nullable: false)]
    #[Groups(['ship:read'])]
    private ?Ship $attackerShip = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'defender_ship_id', referencedColumnName: 'id', nullable: false)]
    #[Groups(['ship:read'])]
    private ?Ship $defenderShip = null;

    #[ORM\Column(length: 20)]
    #[Groups(['ship:read'])]
    private ?string $fightType = null;

    #[ORM\Column]
    #[Groups(['ship:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    #[Groups(['ship:read'])]
    private bool $isRead = false;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->isRead = false;
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

    public function getAttackerShip(): ?Ship
    {
        return $this->attackerShip;
    }

    public function setAttackerShip(?Ship $attackerShip): static
    {
        $this->attackerShip = $attackerShip;

        return $this;
    }

    public function getDefenderShip(): ?Ship
    {
        return $this->defenderShip;
    }

    public function setDefenderShip(?Ship $defenderShip): static
    {
        $this->defenderShip = $defenderShip;

        return $this;
    }

    public function getFightType(): ?string
    {
        return $this->fightType;
    }

    public function setFightType(string $fightType): static
    {
        $this->fightType = $fightType;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function isRead(): bool
    {
        return $this->isRead;
    }

    public function setIsRead(bool $isRead): static
    {
        $this->isRead = $isRead;

        return $this;
    }
}
