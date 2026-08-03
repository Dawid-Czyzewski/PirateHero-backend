<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserCouponRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: UserCouponRepository::class)]
#[ORM\Table(name: 'user_coupon')]
#[ORM\UniqueConstraint(name: 'uniq_user_coupon', columns: ['user_id', 'coupon_id'])]
class UserCoupon
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['user:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'userCoupons')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Coupon::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['user:read'])]
    private ?Coupon $coupon = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['user:read'])]
    private ?\DateTime $usedAt = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['user:read'])]
    private ?array $rewardReceived = null;

    public function __construct()
    {
        $this->usedAt = new \DateTime();
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

    public function getCoupon(): ?Coupon
    {
        return $this->coupon;
    }

    public function setCoupon(?Coupon $coupon): static
    {
        $this->coupon = $coupon;

        return $this;
    }

    public function getUsedAt(): ?\DateTime
    {
        return $this->usedAt;
    }

    public function setUsedAt(\DateTime $usedAt): static
    {
        $this->usedAt = $usedAt;

        return $this;
    }

    public function getRewardReceived(): ?array
    {
        return $this->rewardReceived;
    }

    public function setRewardReceived(?array $rewardReceived): static
    {
        $this->rewardReceived = $rewardReceived;

        return $this;
    }
}
