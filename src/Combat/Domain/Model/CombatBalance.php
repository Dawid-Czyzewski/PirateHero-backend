<?php

declare(strict_types=1);

namespace App\Combat\Domain\Model;

final class CombatBalance
{
    public const HP_PER_ENDURANCE = 10;
    public const HP_PER_LEVEL = 5;

    public const DAMAGE_PER_STRENGTH = 2;
    public const DAMAGE_PER_TECHNIQUE = 1;
    public const DAMAGE_VARIANCE_PERCENT = 20;
    public const MIN_DAMAGE = 1;

    public const MAX_ROUNDS = 20;
    public const STAMINA_COST = 0;

    public const WIN_BASE_EXPERIENCE = 20;
    public const WIN_EXPERIENCE_PER_NPC_LEVEL = 5;
    public const WIN_BASE_ONIONS = 10;
    public const WIN_ONIONS_PER_NPC_LEVEL = 5;
    public const LOSS_EXPERIENCE_PERCENT = 25;
    public const LOSS_ONIONS = 0;

    private function __construct()
    {
    }
}
