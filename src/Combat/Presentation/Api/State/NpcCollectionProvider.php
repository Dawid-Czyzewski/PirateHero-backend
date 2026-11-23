<?php

declare(strict_types=1);

namespace App\Combat\Presentation\Api\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Combat\Domain\Catalog\NpcCatalog;
use App\Combat\Presentation\Api\NpcResource;

/**
 * @implements ProviderInterface<NpcResource>
 */
final readonly class NpcCollectionProvider implements ProviderInterface
{
    public function __construct(private NpcCatalog $npcs)
    {
    }

    /**
     * @return list<NpcResource>
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        return array_map(NpcResource::fromNpc(...), $this->npcs->all());
    }
}
