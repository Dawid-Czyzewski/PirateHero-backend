<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
class FightScore
{
    #[ORM\Column]
    private int $attackerScore;

    #[ORM\Column]
    private int $defenderScore;

    public function __construct(int $attackerScore = 0, int $defenderScore = 0)
    {
        $this->attackerScore = $attackerScore;
        $this->defenderScore = $defenderScore;
    }

    public function getAttackerScore(): int
    {
        return $this->attackerScore;
    }

    public function setAttackerScore(int $attackerScore): self
    {
        $this->attackerScore = $attackerScore;

        return $this;
    }

    public function getDefenderScore(): int
    {
        return $this->defenderScore;
    }

    public function setDefenderScore(int $defenderScore): self
    {
        $this->defenderScore = $defenderScore;

        return $this;
    }
}
