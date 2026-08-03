<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Enum\FightResult;
use App\Repository\UsersFightRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: UsersFightRepository::class)]
#[ApiResource]
class UsersFight
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\ManyToOne(inversedBy: 'userFightsAsAttacker')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $attacker = null;

    #[ORM\ManyToOne(inversedBy: 'userFightsAsDefender')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $defender = null;

    #[ORM\Column(enumType: FightResult::class)]
    #[Groups(['user:read'])]
    private ?FightResult $result = null;

    #[ORM\Embedded(class: FightScore::class)]
    private FightScore $score;

    #[ORM\Column(nullable: true)]
    private ?int $attackerMaxHp = null;

    #[ORM\Column(nullable: true)]
    private ?int $defenderMaxHp = null;

    #[ORM\OneToMany(mappedBy: 'fight', targetEntity: FightMove::class, cascade: ['persist'], orphanRemoval: true)]
    private Collection $fightMoves;

    public function __construct()
    {
        $this->fightMoves = new ArrayCollection();
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

    public function getAttacker(): ?User
    {
        return $this->attacker;
    }

    public function setAttacker(?User $attacker): static
    {
        $this->attacker = $attacker;

        return $this;
    }

    public function getDefender(): ?User
    {
        return $this->defender;
    }

    public function setDefender(?User $defender): static
    {
        $this->defender = $defender;

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

    public function getAttackerMaxHp(): ?int
    {
        return $this->attackerMaxHp;
    }

    public function setAttackerMaxHp(?int $attackerMaxHp): static
    {
        $this->attackerMaxHp = $attackerMaxHp;

        return $this;
    }

    public function getDefenderMaxHp(): ?int
    {
        return $this->defenderMaxHp;
    }

    public function setDefenderMaxHp(?int $defenderMaxHp): static
    {
        $this->defenderMaxHp = $defenderMaxHp;

        return $this;
    }

    public function getFightMoves(): Collection
    {
        return $this->fightMoves;
    }

    public function addFightMove(FightMove $fightMove): static
    {
        if (!$this->fightMoves->contains($fightMove)) {
            $this->fightMoves->add($fightMove);
            $fightMove->setFight($this);
        }

        return $this;
    }

    public function removeFightMove(FightMove $fightMove): static
    {
        if ($this->fightMoves->removeElement($fightMove)) {
            if ($fightMove->getFight() === $this) {
                $fightMove->setFight(null);
            }
        }

        return $this;
    }
}
