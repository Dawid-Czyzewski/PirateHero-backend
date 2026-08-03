<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use App\Enum\CouponRewardType;
use App\Enum\CouponType;
use App\Repository\CouponRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CouponRepository::class)]
#[ORM\Table(name: 'coupon')]
#[ORM\UniqueConstraint(name: 'UNIQ_COUPON_CODE', fields: ['code'])]
#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/coupons/redeem',
            controller: \App\Controller\CouponController::class.'::redeemCoupon',
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
        ),
        new Get(
            uriTemplate: '/coupons/history',
            controller: \App\Controller\CouponController::class.'::getCouponHistory',
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
        ),
    ]
)]
class Coupon
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $code = null;

    #[ORM\Column(type: Types::STRING, enumType: CouponType::class)]
    private ?CouponType $type = null;

    #[ORM\Column(type: Types::STRING, enumType: CouponRewardType::class)]
    private ?CouponRewardType $rewardType = null;

    #[ORM\Column(nullable: true)]
    private ?int $rewardValue = null;

    #[ORM\ManyToOne(targetEntity: BoosterTemplate::class)]
    #[ORM\JoinColumn(name: 'booster_template_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?BoosterTemplate $boosterTemplate = null;

    #[ORM\Column(nullable: true)]
    private ?int $boosterDurationDays = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $rewardData = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTime $expiresAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTime $createdAt = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $usedByUser = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTime $usedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;

        return $this;
    }

    public function getType(): ?CouponType
    {
        return $this->type;
    }

    public function setType(CouponType $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getRewardType(): ?CouponRewardType
    {
        return $this->rewardType;
    }

    public function setRewardType(CouponRewardType $rewardType): static
    {
        $this->rewardType = $rewardType;

        return $this;
    }

    public function getRewardValue(): ?int
    {
        return $this->rewardValue;
    }

    public function setRewardValue(?int $rewardValue): static
    {
        $this->rewardValue = $rewardValue;

        return $this;
    }

    public function getBoosterTemplate(): ?BoosterTemplate
    {
        return $this->boosterTemplate;
    }

    public function setBoosterTemplate(?BoosterTemplate $boosterTemplate): static
    {
        $this->boosterTemplate = $boosterTemplate;

        return $this;
    }

    public function getBoosterDurationDays(): ?int
    {
        return $this->boosterDurationDays;
    }

    public function setBoosterDurationDays(?int $boosterDurationDays): static
    {
        $this->boosterDurationDays = $boosterDurationDays;

        return $this;
    }

    public function getRewardData(): ?array
    {
        return $this->rewardData;
    }

    public function setRewardData(?array $rewardData): static
    {
        $this->rewardData = $rewardData;

        return $this;
    }

    public function getExpiresAt(): ?\DateTime
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(?\DateTime $expiresAt): static
    {
        $this->expiresAt = $expiresAt;

        return $this;
    }

    public function isExpired(): bool
    {
        if ($this->expiresAt === null) {
            return false;
        }

        return $this->expiresAt < new \DateTime();
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUsedByUser(): ?User
    {
        return $this->usedByUser;
    }

    public function setUsedByUser(?User $usedByUser): static
    {
        $this->usedByUser = $usedByUser;

        return $this;
    }

    public function getUsedAt(): ?\DateTime
    {
        return $this->usedAt;
    }

    public function setUsedAt(?\DateTime $usedAt): static
    {
        $this->usedAt = $usedAt;

        return $this;
    }

    public function isUsed(): bool
    {
        return $this->usedAt !== null;
    }
}
