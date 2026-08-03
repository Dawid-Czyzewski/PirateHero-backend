<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use App\Controller\TrainingController;
use App\Enum\UserStatType;
use App\Repository\TrainingRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: TrainingRepository::class)]
#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/trainings/{id}/start',
            controller: TrainingController::class.'::startTraining',
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            name: 'start_training'
        ),
        new Post(
            uriTemplate: '/trainings/{id}/cancel',
            controller: TrainingController::class.'::cancelTraining',
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            name: 'cancel_training'
        ),
        new Post(
            uriTemplate: '/trainings/{id}/complete',
            controller: TrainingController::class.'::completeTraining',
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            name: 'complete_training'
        ),
    ],
)]
class Training
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['user:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['user:read'])]
    private ?string $title = null;

    #[ORM\Column(length: 255)]
    #[Groups(['user:read'])]
    private ?string $description = null;

    #[ORM\Column]
    #[Groups(['user:read'])]
    private ?int $durationInSeconds = null;

    #[ORM\Column]
    #[Groups(['user:read'])]
    private ?int $trainingPointsCost = null;

    #[ORM\Column]
    #[Groups(['user:read'])]
    private ?int $skillPointsReward = null;

    #[ORM\Column(enumType: UserStatType::class)]
    #[Groups(['user:read'])]
    private ?UserStatType $statType = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'missions', cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getDurationInSeconds(): ?int
    {
        return $this->durationInSeconds;
    }

    public function setDurationInSeconds(int $durationInSeconds): static
    {
        $this->durationInSeconds = $durationInSeconds;

        return $this;
    }

    public function getTrainingPointsCost(): ?int
    {
        return $this->trainingPointsCost;
    }

    public function setTrainingPointsCost(int $trainingPointsCost): static
    {
        $this->trainingPointsCost = $trainingPointsCost;

        return $this;
    }

    public function getSkillPointsReward(): ?int
    {
        return $this->skillPointsReward;
    }

    public function setSkillPointsReward(int $skillPointsReward): static
    {
        $this->skillPointsReward = $skillPointsReward;

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

    public function getStatType(): ?UserStatType
    {
        return $this->statType;
    }

    public function setStatType(UserStatType $statType): static
    {
        $this->statType = $statType;

        return $this;
    }
}
