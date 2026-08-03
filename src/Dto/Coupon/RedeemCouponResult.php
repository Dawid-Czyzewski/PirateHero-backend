<?php

declare(strict_types=1);

namespace App\Dto\Coupon;

final readonly class RedeemCouponResult
{
    public function __construct(
        public bool $success,
        public array $reward,
        public string $code,
        public string $rewardType,
    ) {
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'reward' => $this->reward,
            'coupon' => [
                'code' => $this->code,
                'rewardType' => $this->rewardType,
            ],
        ];
    }
}
