<?php

declare(strict_types=1);

namespace App\Character\Application\Query\GetMyCharacter;

use App\Character\Domain\Model\Character;

final readonly class CharacterView
{
    /**
     * @param array{strength: int, endurance: int, technique: int, charisma: int}             $stats
     * @param array{cebuliony: int, zloteZeby: int, kondycha: int, zasiegi: int, medale: int} $wallet
     * @param array{face: int, hair: int, beard: int, skin: int, tattoo: int}                 $appearance
     */
    public function __construct(
        public string $id,
        public int $level,
        public int $maxHp,
        public array $stats,
        public array $wallet,
        public array $appearance,
    ) {
    }

    public static function fromCharacter(Character $character): self
    {
        $stats = $character->stats();
        $wallet = $character->wallet();
        $appearance = $character->appearance();

        return new self(
            $character->id()->toString(),
            $character->level()->value(),
            $character->maxHp(),
            [
                'strength' => $stats->strength(),
                'endurance' => $stats->endurance(),
                'technique' => $stats->technique(),
                'charisma' => $stats->charisma(),
            ],
            [
                'cebuliony' => $wallet->cebuliony(),
                'zloteZeby' => $wallet->zloteZeby(),
                'kondycha' => $wallet->kondycha(),
                'zasiegi' => $wallet->zasiegi(),
                'medale' => $wallet->medale(),
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
