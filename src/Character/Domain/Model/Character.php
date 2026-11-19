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
        private Experience $experience,
        private Stats $stats,
        private Stamina $stamina,
        private Wallet $wallet,
        private Appearance $appearance,
    ) {
    }

    public static function create(CharacterId $id, UserId $userId, Appearance $appearance, \DateTimeImmutable $now): self
    {
        return new self(
            $id,
            $userId,
            Level::start(),
            Experience::none(),
            Stats::initial(),
            Stamina::full($now),
            Wallet::initial(),
            $appearance,
        );
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

    public function experience(): Experience
    {
        return $this->experience;
    }

    public function gainExperience(int $points): void
    {
        $experience = $this->experience->gain($points);
        $level = $this->level;

        while ($experience->reaches($level->requiredExperience())) {
            $experience = $experience->spend($level->requiredExperience());
            $level = $level->next();
        }

        $this->level = $level;
        $this->experience = $experience;
    }

    public function stats(): Stats
    {
        return $this->stats;
    }

    public function stamina(): Stamina
    {
        return $this->stamina;
    }

    public function spendStamina(int $cost, \DateTimeImmutable $now): void
    {
        $this->stamina = $this->stamina->spend($cost, $now);
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
