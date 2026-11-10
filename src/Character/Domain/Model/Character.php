<?php

declare(strict_types=1);

namespace App\Character\Domain\Model;

use App\Shared\Domain\Model\UserId;

class Character
{
    private function __construct(
        private readonly CharacterId $id,
        private readonly UserId $userId,
        private Level $level,
        private Stats $stats,
        private Wallet $wallet,
        private Appearance $appearance,
    ) {
    }

    public static function create(CharacterId $id, UserId $userId, Appearance $appearance): self
    {
        return new self($id, $userId, Level::start(), Stats::initial(), Wallet::initial(), $appearance);
    }

    public function id(): CharacterId
    {
        return $this->id;
    }

    public function userId(): UserId
    {
        return $this->userId;
    }

    public function level(): Level
    {
        return $this->level;
    }

    public function stats(): Stats
    {
        return $this->stats;
    }

    public function wallet(): Wallet
    {
        return $this->wallet;
    }

    public function appearance(): Appearance
    {
        return $this->appearance;
    }

    public function maxHp(): int
    {
        return HitPoints::maximum($this->stats, $this->level)->value();
    }
}
