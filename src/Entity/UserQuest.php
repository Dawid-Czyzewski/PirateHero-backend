<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use App\Controller\QuestTaskController;
use App\Exception\BusinessRuleException;
use App\Repository\UserQuestRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: UserQuestRepository::class)]
#[ORM\Table(name: 'user_quest')]
#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/user_quests',
            controller: QuestTaskController::class.'::getUserQuests',
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            securityMessage: 'Access denied. You must be authenticated to access quests.',
            name: 'api_user_quests_get'
        ),
        new Post(
            uriTemplate: '/user_quests/{id}/claim-reward',
            controller: QuestTaskController::class.'::claimReward',
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            securityMessage: 'Access denied. You must be authenticated to claim quest rewards.',
            name: 'api_user_quests_claim_reward'
        ),
    ]
)]
class UserQuest
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['user:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'userQuests')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: QuestTemplate::class)]
    #[ORM\JoinColumn(name: 'quest_template_id', nullable: false)]
    #[Groups(['user:read'])]
    private ?QuestTemplate $questTemplate = null;

    #[ORM\Column]
    #[Groups(['user:read'])]
    private int $currentProgress = 0;

    #[ORM\Column]
    #[Groups(['user:read'])]
    private bool $isCompleted = false;

    #[ORM\Column]
    #[Groups(['user:read'])]
    private bool $isRewardClaimed = false;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $completedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
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

    public function getQuestTemplate(): ?QuestTemplate
    {
        return $this->questTemplate;
    }

    public function setQuestTemplate(?QuestTemplate $questTemplate): static
    {
        $this->questTemplate = $questTemplate;

        return $this;
    }

    public function getCurrentProgress(): int
    {
        return $this->currentProgress;
    }

    public function setCurrentProgress(int $currentProgress): static
    {
        $this->currentProgress = $currentProgress;

        if (!$this->isCompleted && $this->questTemplate && $this->currentProgress >= $this->questTemplate->getTargetValue()) {
            $this->isCompleted = true;
            $this->completedAt = new \DateTimeImmutable();
        }

        return $this;
    }

    public function addProgress(int $amount): static
    {
        $this->setCurrentProgress($this->currentProgress + $amount);

        return $this;
    }

    public function isCompleted(): bool
    {
        return $this->isCompleted;
    }

    public function setIsCompleted(bool $isCompleted): static
    {
        $this->isCompleted = $isCompleted;
        if ($isCompleted && !$this->completedAt) {
            $this->completedAt = new \DateTimeImmutable();
        }

        return $this;
    }

    public function isRewardClaimed(): bool
    {
        return $this->isRewardClaimed;
    }

    public function setIsRewardClaimed(bool $isRewardClaimed): static
    {
        $this->isRewardClaimed = $isRewardClaimed;

        return $this;
    }

    public function claimReward(): static
    {
        if (!$this->isCompleted) {
            throw new BusinessRuleException('questNotCompleted');
        }
        if ($this->isRewardClaimed) {
            throw new BusinessRuleException('questRewardAlreadyClaimed');
        }
        $this->isRewardClaimed = true;

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

    public function getCompletedAt(): ?\DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function setCompletedAt(?\DateTimeImmutable $completedAt): static
    {
        $this->completedAt = $completedAt;

        return $this;
    }

    public function getProgressPercentage(): float
    {
        if (!$this->questTemplate || $this->questTemplate->getTargetValue() === 0) {
            return 0;
        }

        return min(100, ($this->currentProgress / $this->questTemplate->getTargetValue()) * 100);
    }
}
