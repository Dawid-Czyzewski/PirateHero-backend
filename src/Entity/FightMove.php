<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Enum\FightMoveResult;
use App\Repository\FightMoveRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: FightMoveRepository::class)]
#[ApiResource]
class FightMove
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'fightMoves')]
    #[ORM\JoinColumn(nullable: false)]
    private ?UsersFight $fight = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['user:read'])]
    private ?User $player = null;

    #[ORM\Column(enumType: FightMoveResult::class)]
    #[Groups(['user:read'])]
    private ?FightMoveResult $result = null;

    #[ORM\Column]
    #[Groups(['user:read'])]
    private ?int $moveNumber = null;

    #[ORM\Column]
    #[Groups(['user:read'])]
    private ?int $damage = null;

    #[ORM\Column]
    #[Groups(['user:read'])]
    private ?int $attackerHealthAfter = null;

    #[ORM\Column]
    #[Groups(['user:read'])]
    private ?int $defenderHealthAfter = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFight(): ?UsersFight
    {
        return $this->fight;
    }

    public function setFight(?UsersFight $fight): static
    {
        $this->fight = $fight;

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

    public function getAttackerHealthAfter(): ?int
    {
        return $this->attackerHealthAfter;
    }

    public function setAttackerHealthAfter(int $attackerHealthAfter): static
    {
        $this->attackerHealthAfter = $attackerHealthAfter;

        return $this;
    }

    public function getDefenderHealthAfter(): ?int
    {
        return $this->defenderHealthAfter;
    }

    public function setDefenderHealthAfter(int $defenderHealthAfter): static
    {
        $this->defenderHealthAfter = $defenderHealthAfter;

        return $this;
    }
}
