<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use App\Controller\EnergyRefillController;
use App\Controller\FightRefillController;
use App\Controller\TrainingRefillController;
use App\Enum\RefillType;
use App\Repository\UserRefillRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserRefillRepository::class)]
#[ORM\UniqueConstraint(name: 'user_refill_unique', columns: ['user_id', 'type'])]
#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/users/energy/refill',
            controller: EnergyRefillController::class.'::refillEnergy',
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
        ),
        new Get(
            uriTemplate: '/users/energy/refill/info',
            controller: EnergyRefillController::class.'::getRefillInfo',
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
        ),
        new Post(
            uriTemplate: '/users/training/refill',
            controller: TrainingRefillController::class.'::refillTraining',
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
        ),
        new Get(
            uriTemplate: '/users/training/refill/info',
            controller: TrainingRefillController::class.'::getRefillInfo',
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
        ),
        new Post(
            uriTemplate: '/users/fight/refill',
            controller: FightRefillController::class.'::refillFight',
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
        ),
        new Get(
            uriTemplate: '/users/fight/refill/info',
            controller: FightRefillController::class.'::getRefillInfo',
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
        ),
    ]
)]
class UserRefill
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'userRefills')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(type: Types::STRING, enumType: RefillType::class)]
    private ?RefillType $type = null;

    #[ORM\Column]
    private ?int $refillCount = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTime $lastRefillDate = null;

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

    public function getType(): ?RefillType
    {
        return $this->type;
    }

    public function setType(RefillType $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getRefillCount(): ?int
    {
        return $this->refillCount;
    }

    public function setRefillCount(int $refillCount): static
    {
        $this->refillCount = $refillCount;

        return $this;
    }

    public function getLastRefillDate(): ?\DateTime
    {
        return $this->lastRefillDate;
    }

    public function setLastRefillDate(?\DateTime $lastRefillDate): static
    {
        $this->lastRefillDate = $lastRefillDate;

        return $this;
    }
}
