<?php

declare(strict_types=1);

namespace App\Character\Application\Query\GetMyCharacter;

use App\Character\Domain\Model\Character;
use App\Character\Domain\Model\Stamina;

final readonly class CharacterView
{
    /**
     * @param array{current: int, required: int}                                  $experience
     * @param array{current: int, max: int, nextPointInSeconds: int|null}         $stamina
     * @param array{strength: int, endurance: int, technique: int, charisma: int} $stats
     * @param array{onions: int, goldTeeth: int, reach: int, medals: int}         $wallet
     * @param array{face: int, hair: int, beard: int, skin: int, tattoo: int}     $appearance
     */
    public function __construct(
        public string $id,
        public string $nick,
        public int $level,
        public int $maxHp,
        public array $experience,
        public array $stamina,
        public array $stats,
        public array $wallet,
        public array $appearance,
    ) {
    }

    public static function fromCharacter(Character $character, string $nick, \DateTimeImmutable $now): self
    {
        $level = $character->level();
        $stamina = $character->stamina();
        $stats = $character->stats();
        $wallet = $character->wallet();
        $appearance = $character->appearance();

        return new self(
            $character->id()->toString(),
            $nick,
            $level->value(),
            $character->maxHp(),
            [
                'current' => $character->experience()->points(),
                'required' => $level->requiredExperience(),
            ],
            [
                'current' => $stamina->current($now),
                'max' => Stamina::MAX,
                'nextPointInSeconds' => $stamina->secondsToNextPoint($now),
            ],
            [
                'strength' => $stats->strength(),
                'endurance' => $stats->endurance(),
                'technique' => $stats->technique(),
                'charisma' => $stats->charisma(),
            ],
            [
                'onions' => $wallet->onions(),
                'goldTeeth' => $wallet->goldTeeth(),
                'reach' => $wallet->reach(),
                'medals' => $wallet->medals(),
            ],
            [
                'face' => $appearance->face(),
                'hair' => $appearance->hair(),
                'beard' => $appearance->beard(),
                'skin' => $appearance->skin(),
                'tattoo' => $appearance->tattoo(),
            ],
        );
    }
}
