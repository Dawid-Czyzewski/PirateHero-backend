<?php

declare(strict_types=1);

namespace App\Combat\Domain\Service;

use App\Combat\Domain\Model\CombatBalance;
use App\Combat\Domain\Model\Fighter;
use App\Combat\Domain\Model\FighterSide;
use App\Combat\Domain\Model\FightLog;
use App\Combat\Domain\Model\FightResult;
use App\Combat\Domain\Model\Round;
use App\Combat\Domain\Random\RandomIntProvider;

final readonly class FightEngine
{
    public function __construct(private RandomIntProvider $random)
    {
    }

    public function fight(Fighter $player, Fighter $npc): FightResult
    {
        $fighters = [FighterSide::Player->value => $player, FighterSide::Npc->value => $npc];
        $hp = [FighterSide::Player->value => $player->maxHp(), FighterSide::Npc->value => $npc->maxHp()];
        $attacker = self::initiative($player, $npc);
        $rounds = [];

        for ($number = 1; $number <= CombatBalance::MAX_ROUNDS; ++$number) {
            $defender = $attacker->opponent();
            $damage = $this->damage($fighters[$attacker->value]);
            $hp[$defender->value] = max(0, $hp[$defender->value] - $damage);
            $rounds[] = new Round($number, $attacker, $damage, $hp[FighterSide::Player->value], $hp[FighterSide::Npc->value]);

            if (0 === $hp[$defender->value]) {
                return new FightResult($attacker, true, new FightLog($rounds));
            }
            $attacker = $defender;
        }

        return new FightResult(
            self::onPoints($player, $hp[FighterSide::Player->value], $npc, $hp[FighterSide::Npc->value]),
            false,
            new FightLog($rounds),
        );
    }

    public function damage(Fighter $attacker): int
    {
        $variance = $this->random->between(-CombatBalance::DAMAGE_VARIANCE_PERCENT, CombatBalance::DAMAGE_VARIANCE_PERCENT);

        return max(CombatBalance::MIN_DAMAGE, (int) round($attacker->baseDamage() * (100 + $variance) / 100));
    }

    private static function initiative(Fighter $player, Fighter $npc): FighterSide
    {
        return $npc->technique() > $player->technique() ? FighterSide::Npc : FighterSide::Player;
    }

    private static function onPoints(Fighter $player, int $playerHp, Fighter $npc, int $npcHp): FighterSide
    {
        return $playerHp * $npc->maxHp() >= $npcHp * $player->maxHp() ? FighterSide::Player : FighterSide::Npc;
    }
}
