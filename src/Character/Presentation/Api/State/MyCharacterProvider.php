<?php

declare(strict_types=1);

namespace App\Character\Presentation\Api\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Character\Application\Query\GetMyCharacter\GetMyCharacterHandler;
use App\Character\Application\Query\GetMyCharacter\GetMyCharacterQuery;
use App\Character\Domain\Exception\CharacterNotFound;
use App\Character\Presentation\Api\CharacterResource;
use App\Shared\Application\Security\CurrentUser;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * @implements ProviderInterface<CharacterResource>
 */
final readonly class MyCharacterProvider implements ProviderInterface
{
    public function __construct(
        private CurrentUser $currentUser,
        private GetMyCharacterHandler $getMyCharacter,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): CharacterResource
    {
        $userId = $this->currentUser->userId() ?? throw new AccessDeniedException();

        try {
            return CharacterResource::fromView(($this->getMyCharacter)(new GetMyCharacterQuery($userId->toString())));
        } catch (CharacterNotFound) {
            throw new NotFoundHttpException('Character not found.');
        }
    }
}
