<?php

declare(strict_types=1);

namespace App\Character\Presentation\Api;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation as OpenApiOperation;
use ApiPlatform\OpenApi\Model\Response as OpenApiResponse;
use App\Character\Application\Query\GetMyCharacter\CharacterView;
use App\Character\Presentation\Api\Output\CharacterAppearance;
use App\Character\Presentation\Api\Output\CharacterStats;
use App\Character\Presentation\Api\Output\CharacterWallet;
use App\Character\Presentation\Api\State\CreateCharacterProcessor;
use App\Character\Presentation\Api\State\MyCharacterProvider;

#[ApiResource(
    shortName: 'Character',
    operations: [
        new Post(
            uriTemplate: '/characters',
            status: 201,
            description: 'Creates the character of the authenticated player.',
            openapi: new OpenApiOperation(responses: [
                '401' => new OpenApiResponse('Missing or invalid JWT.'),
                '409' => new OpenApiResponse('Character already exists.'),
            ]),
            security: "is_granted('ROLE_USER')",
            input: CreateCharacterInput::class,
            processor: CreateCharacterProcessor::class,
        ),
        new Get(
            uriTemplate: '/me/character',
            description: 'Returns the character of the authenticated player.',
            openapi: new OpenApiOperation(responses: [
                '401' => new OpenApiResponse('Missing or invalid JWT.'),
                '404' => new OpenApiResponse('Character not found.'),
            ]),
            security: "is_granted('ROLE_USER')",
            provider: MyCharacterProvider::class,
        ),
    ],
)]
final class CharacterResource
{
    public function __construct(
        #[ApiProperty(identifier: true)]
        public string $id,
        public string $nick,
        public int $level,
        public int $maxHp,
        public CharacterStats $stats,
        public CharacterWallet $wallet,
        public CharacterAppearance $appearance,
    ) {
    }

    public static function fromView(CharacterView $view): self
    {
        return new self(
            $view->id,
            $view->nick,
            $view->level,
            $view->maxHp,
            new CharacterStats(...$view->stats),
            new CharacterWallet(...$view->wallet),
            new CharacterAppearance(...$view->appearance),
        );
    }
}
