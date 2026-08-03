<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\QuestCategory;
use App\Enum\QuestRewardType;
use App\Repository\QuestTemplateRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: QuestTemplateRepository::class)]
#[ORM\Table(name: 'quest_template')]
class QuestTemplate
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['user:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['user:read'])]
    private ?string $title = null;

    #[ORM\Column(length: 64, unique: true, nullable: true)]
    #[Groups(['user:read'])]
    private ?string $code = null;

    #[ORM\Column(type: 'text')]
    #[Groups(['user:read'])]
    private ?string $description = null;

    #[ORM\Column(length: 32, nullable: true)]
    #[Groups(['user:read'])]
    private ?string $targetDungeonId = null;

    #[ORM\Column(enumType: QuestCategory::class)]
    #[Groups(['user:read'])]
    private ?QuestCategory $category = null;

    #[ORM\Column]
    #[Groups(['user:read'])]
    private int $targetValue = 0;

    #[ORM\Column(enumType: QuestRewardType::class)]
    #[Groups(['user:read'])]
    private ?QuestRewardType $rewardType = null;

    #[ORM\Column]
    #[Groups(['user:read'])]
    private int $rewardAmount = 0;

    #[ORM\Column(enumType: QuestRewardType::class, nullable: true)]
    #[Groups(['user:read'])]
    private ?QuestRewardType $secondaryRewardType = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['user:read'])]
    private ?int $secondaryRewardAmount = null;

    #[ORM\ManyToOne(targetEntity: WearableItem::class)]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['user:read'])]
    private ?WearableItem $rewardItem = null;

    #[ORM\Column]
    #[Groups(['user:read'])]
    private bool $isActive = true;

    #[ORM\Column(name: '`order`')]
    #[Groups(['user:read'])]
    private int $order = 0;

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

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): static
    {
        $this->code = $code;

        return $this;
    }

    public function getTargetDungeonId(): ?string
    {
        return $this->targetDungeonId;
    }

    public function setTargetDungeonId(?string $targetDungeonId): static
    {
        $this->targetDungeonId = $targetDungeonId;

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

    public function getCategory(): ?QuestCategory
    {
        return $this->category;
    }

    public function setCategory(QuestCategory $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function getTargetValue(): int
    {
        return $this->targetValue;
    }

    public function setTargetValue(int $targetValue): static
    {
        $this->targetValue = $targetValue;

        return $this;
    }

    public function getRewardType(): ?QuestRewardType
    {
        return $this->rewardType;
    }

    public function setRewardType(QuestRewardType $rewardType): static
    {
        $this->rewardType = $rewardType;

        return $this;
    }

    public function getRewardAmount(): int
    {
        return $this->rewardAmount;
    }

    public function setRewardAmount(int $rewardAmount): static
    {
        $this->rewardAmount = $rewardAmount;

        return $this;
    }

    public function getSecondaryRewardType(): ?QuestRewardType
    {
        return $this->secondaryRewardType;
    }

    public function setSecondaryRewardType(?QuestRewardType $secondaryRewardType): static
    {
        $this->secondaryRewardType = $secondaryRewardType;

        return $this;
    }

    public function getSecondaryRewardAmount(): ?int
    {
        return $this->secondaryRewardAmount;
    }

    public function setSecondaryRewardAmount(?int $secondaryRewardAmount): static
    {
        $this->secondaryRewardAmount = $secondaryRewardAmount;

        return $this;
    }

    public function getRewardItem(): ?WearableItem
    {
        return $this->rewardItem;
    }

    public function setRewardItem(?WearableItem $rewardItem): static
    {
        $this->rewardItem = $rewardItem;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function getOrder(): int
    {
        return $this->order;
    }

    public function setOrder(int $order): static
    {
        $this->order = $order;

        return $this;
    }
}
