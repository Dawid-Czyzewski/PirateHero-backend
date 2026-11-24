<?php

declare(strict_types=1);

namespace App\Combat\Presentation\Api;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation as OpenApiOperation;
use ApiPlatform\OpenApi\Model\Response as OpenApiResponse;
use App\Combat\Application\Command\FightNpc\FightView;
use App\Combat\Presentation\Api\State\FightNpcProcessor;

#[ApiResource(
    shortName: 'Fight',
    operations: [
        new Post(
            uriTemplate: '/me/fights',
            status: 200,
            description: 'Fights an NPC with the character of the authenticated player and awards experience and onions.',
            openapi: new OpenApiOperation(responses: [
                '401' => new OpenApiResponse('Missing or invalid JWT.'),
                '404' => new OpenApiResponse('Character or NPC not found.'),
                '409' => new OpenApiResponse('Not enough fight points.'),
            ]),
            security: "is_granted('ROLE_USER')",
            input: FightNpcInput::class,
            processor: FightNpcProcessor::class,
        ),
    ],
    normalizationContext: ['skip_null_values' => false],
)]
final readonly class FightResource
{
    /**
     * @param array{name: string, level: int, strength: int, endurance: int, technique: int, charisma: int, maxHp: int}                                                                                                    $player
     * @param array{id: string, look: array{face: int, hair: int, beard: int, skin: int, tattoo: int}, fighter: array{name: string, level: int, strength: int, endurance: int, technique: int, charisma: int, maxHp: int}} $npc
     * @param list<array{number: int, attacker: string, damage: int, playerHp: int, npcHp: int}>                                                                                                                           $rounds
     * @param array{experience: int, onions: int}                                                                                                                                                                          $reward
     * @param array{before: int, after: int}                                                                                                                                                                               $level
     */
    public function __construct(
        public array $player,
        public array $npc,
        public string $winner,
        public bool $knockout,
        public array $rounds,
        public array $reward,
        public array $level,
        public int $fightPointCost,
    ) {
    }

    public static function fromView(FightView $view): self
    {
        return new self(
            $view->player,
            $view->npc,
            $view->result['winner'],
            $view->result['knockout'],
            $view->result['rounds'],
            $view->reward,
            $view->level,
            $view->fightPointCost,
        );
    }
}
