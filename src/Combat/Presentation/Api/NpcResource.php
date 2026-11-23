<?php

declare(strict_types=1);

namespace App\Combat\Presentation\Api;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\OpenApi\Model\Operation as OpenApiOperation;
use ApiPlatform\OpenApi\Model\Response as OpenApiResponse;
use App\Character\Presentation\Api\Output\CharacterAppearance;
use App\Character\Presentation\Api\Output\CharacterStats;
use App\Combat\Domain\Model\Npc;
use App\Combat\Presentation\Api\State\NpcCollectionProvider;

#[ApiResource(
    shortName: 'Npc',
    operations: [
        new GetCollection(
            uriTemplate: '/npcs',
            paginationEnabled: false,
            description: 'Lists the NPC opponents of the Octagon, ordered by level.',
            openapi: new OpenApiOperation(responses: [
                '401' => new OpenApiResponse('Missing or invalid JWT.'),
            ]),
            security: "is_granted('ROLE_USER')",
            provider: NpcCollectionProvider::class,
        ),
    ],
)]
final class NpcResource
{
    public function __construct(
        #[ApiProperty(identifier: true)]
        public string $id,
        public string $nick,
        public int $level,
        public int $maxHp,
        public CharacterStats $stats,
        public CharacterAppearance $look,
    ) {
    }

    public static function fromNpc(Npc $npc): self
    {
        $fighter = $npc->fighter;
        $look = $npc->look;

        return new self(
            $npc->id,
            $npc->nick(),
            $fighter->level(),
            $fighter->maxHp(),
            new CharacterStats($fighter->strength(), $fighter->endurance(), $fighter->technique(), $fighter->charisma()),
            new CharacterAppearance($look->face, $look->hair, $look->beard, $look->skin, $look->tattoo),
        );
    }
}
