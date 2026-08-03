<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\FightMoveResult;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ORM\Table(name: 'ships_fight_move')]
class ShipsFightMove
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'fightMoves')]
    #[ORM\JoinColumn(name: 'ships_fight_id', referencedColumnName: 'id', nullable: false)]
    private ?ShipsFight $shipsFight = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['ship:read'])]
    private ?User $player = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['ship:read'])]
    private ?User $target = null;

    #[ORM\Column(enumType: FightMoveResult::class)]
    #[Groups(['ship:read'])]
    private ?FightMoveResult $result = null;

    #[ORM\Column]
    #[Groups(['ship:read'])]
    private ?int $moveNumber = null;

    #[ORM\Column]
    #[Groups(['ship:read'])]
    private ?int $damage = null;

    #[ORM\Column]
    #[Groups(['ship:read'])]
    private ?int $targetHealthAfter = null;

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

    public function getPlayer(): ?User
    {
        return $this->player;
    }

    public function setPlayer(?User $player): static
    {
        $this->player = $player;

        return $this;
    }

    public function getTarget(): ?User
    {
        return $this->target;
    }

    public function setTarget(?User $target): static
    {
        $this->target = $target;

        return $this;
    }

    public function getResult(): ?FightMoveResult
    {
        return $this->result;
    }

    public function setResult(?FightMoveResult $result): static
    {
        $this->result = $result;

        return $this;
    }

    public function getMoveNumber(): ?int
    {
        return $this->moveNumber;
    }

    public function setMoveNumber(int $moveNumber): static
    {
        $this->moveNumber = $moveNumber;

        return $this;
    }

    public function getDamage(): ?int
    {
        return $this->damage;
    }

    public function setDamage(int $damage): static
    {
        $this->damage = $damage;

        return $this;
    }

    public function getTargetHealthAfter(): ?int
    {
        return $this->targetHealthAfter;
    }

    public function setTargetHealthAfter(int $targetHealthAfter): static
    {
        $this->targetHealthAfter = $targetHealthAfter;

        return $this;
    }
}
