<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Enum\FightResult;
use App\Repository\ShipsFightRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ShipsFightRepository::class)]
#[ORM\Table(name: 'ships_fight')]
#[ApiResource]
class ShipsFight
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'attacker_ship_id', referencedColumnName: 'id', nullable: false)]
    private ?Ship $attackerShip = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'defender_ship_id', referencedColumnName: 'id', nullable: false)]
    private ?Ship $defenderShip = null;

    #[ORM\Column(enumType: FightResult::class)]
    #[Groups(['ship:read'])]
    private ?FightResult $result = null;

    #[ORM\Embedded(class: FightScore::class)]
    private FightScore $score;

    #[ORM\OneToMany(mappedBy: 'shipsFight', targetEntity: ShipsFightMember::class, cascade: ['persist'], orphanRemoval: true)]
    private Collection $fightMembers;

    #[ORM\OneToMany(mappedBy: 'shipsFight', targetEntity: ShipsFightMove::class, cascade: ['persist'], orphanRemoval: true)]
    private Collection $fightMoves;

    public function __construct()
    {
        $this->fightMembers = new ArrayCollection();
        $this->fightMoves = new ArrayCollection();
        $this->score = new FightScore();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getResult(): ?FightResult
    {
        return $this->result;
    }

    public function setResult(?FightResult $result): static
    {
        $this->result = $result;

        return $this;
    }

    public function getScore(): FightScore
    {
        return $this->score;
    }

    public function setScore(FightScore $score): static
    {
        $this->score = $score;

        return $this;
    }

    public function getFightMembers(): Collection
    {
        return $this->fightMembers;
    }

    public function addFightMember(ShipsFightMember $fightMember): static
    {
        if (!$this->fightMembers->contains($fightMember)) {
            $this->fightMembers->add($fightMember);
            $fightMember->setShipsFight($this);
        }

        return $this;
    }

    public function removeFightMember(ShipsFightMember $fightMember): static
    {
        if ($this->fightMembers->removeElement($fightMember)) {
            if ($fightMember->getShipsFight() === $this) {
                $fightMember->setShipsFight(null);
            }
        }

        return $this;
    }

    public function getFightMoves(): Collection
    {
        return $this->fightMoves;
    }

    public function addFightMove(ShipsFightMove $fightMove): static
    {
        if (!$this->fightMoves->contains($fightMove)) {
            $this->fightMoves->add($fightMove);
            $fightMove->setShipsFight($this);
        }

        return $this;
    }

    public function removeFightMove(ShipsFightMove $fightMove): static
    {
        if ($this->fightMoves->removeElement($fightMove)) {
            if ($fightMove->getShipsFight() === $this) {
                $fightMove->setShipsFight(null);
            }
        }

        return $this;
    }
}
