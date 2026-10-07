<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserTreasureMapRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserTreasureMapRepository::class)]
#[ORM\Table(name: 'user_treasure_map')]
#[ORM\UniqueConstraint(name: 'UNIQ_USER_TREASURE_MAP_WEEK', fields: ['user', 'weekStart'])]
class UserTreasureMap
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private \DateTimeImmutable $weekStart;

    #[ORM\Column]
    private int $currentStep = 1;

    #[ORM\Column(type: Types::JSON)]
    private array $stepProgress = [0, 0, 0, 0, 0];

    #[ORM\Column(options: ['default' => false])]
    private bool $completed = false;

    #[ORM\Column(options: ['default' => false])]
    private bool $chestClaimed = false;

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

    public function getWeekStart(): \DateTimeImmutable
    {
        return $this->weekStart;
    }

    public function setWeekStart(\DateTimeImmutable $weekStart): static
    {
        $this->weekStart = $weekStart;

        return $this;
    }

    public function getCurrentStep(): int
    {
        return $this->currentStep;
    }

    public function setCurrentStep(int $currentStep): static
    {
        $this->currentStep = max(1, $currentStep);

        return $this;
    }

    /**
     * @return list<int>
     */
    public function getStepProgress(): array
    {
        return $this->stepProgress;
    }

    /**
     * @param list<int> $stepProgress
     */
    public function setStepProgress(array $stepProgress): static
    {
        $this->stepProgress = $stepProgress;

        return $this;
    }

    public function isCompleted(): bool
    {
        return $this->completed;
    }

    public function setCompleted(bool $completed): static
    {
        $this->completed = $completed;

        return $this;
    }

    public function isChestClaimed(): bool
    {
        return $this->chestClaimed;
    }

    public function setChestClaimed(bool $chestClaimed): static
    {
        $this->chestClaimed = $chestClaimed;

        return $this;
    }
}
