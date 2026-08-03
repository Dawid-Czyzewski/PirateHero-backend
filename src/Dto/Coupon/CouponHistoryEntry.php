<?php

declare(strict_types=1);

namespace App\Dto\Coupon;

final readonly class CouponHistoryEntry
{
    public function __construct(
        public int $id,
        public string $code,
        public string $rewardType,
        public ?array $rewardReceived,
        public string $usedAt,
    ) {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'rewardType' => $this->rewardType,
            'rewardReceived' => $this->rewardReceived,
            'usedAt' => $this->usedAt,
        ];
    }
}
