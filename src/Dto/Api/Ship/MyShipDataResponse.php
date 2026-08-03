<?php

declare(strict_types=1);

namespace App\Dto\Api\Ship;

final readonly class MyShipDataResponse
{
    /**
     * @param array<string, mixed> $shipUpgradePricing
     * @param list<ShipMemberDto> $members
     * @param list<ShipMessageDto> $messages
     */
    public function __construct(
        public array $shipUpgradePricing,
        public ShipSummaryDto $ship,
        public ?ShipMemberSelfDto $member,
        public array $members,
        public array $messages,
    ) {
    }

    public function toArray(): array
    {
        return [
            'shipUpgradePricing' => $this->shipUpgradePricing,
            'ship' => $this->ship->toArray(),
            'member' => $this->member?->toArray(),
            'members' => array_map(static fn (ShipMemberDto $m) => $m->toArray(), $this->members),
            'messages' => array_map(static fn (ShipMessageDto $m) => $m->toArray(), $this->messages),
        ];
    }
}
