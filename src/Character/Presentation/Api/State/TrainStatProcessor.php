<?php

declare(strict_types=1);

namespace App\Character\Presentation\Api\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Character\Application\Command\TrainStat\TrainStatHandler;
use App\Character\Application\Query\GetMyCharacter\GetMyCharacterHandler;
use App\Character\Application\Query\GetMyCharacter\GetMyCharacterQuery;
use App\Character\Domain\Exception\CharacterNotFound;
use App\Character\Domain\Exception\InsufficientStamina;
use App\Character\Presentation\Api\CharacterResource;
use App\Character\Presentation\Api\TrainStatInput;
use App\Shared\Application\Security\CurrentUser;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * @implements ProcessorInterface<TrainStatInput, CharacterResource>
 */
final readonly class TrainStatProcessor implements ProcessorInterface
{
    public function __construct(
        private CurrentUser $currentUser,
        private TrainStatHandler $trainStat,
        private GetMyCharacterHandler $getMyCharacter,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): CharacterResource
    {
        $userId = ($this->currentUser->userId() ?? throw new AccessDeniedException())->toString();

        try {
            ($this->trainStat)($data->toCommand($userId));
        } catch (CharacterNotFound) {
            throw new NotFoundHttpException('Character not found.');
        } catch (InsufficientStamina $e) {
            throw new ConflictHttpException(\sprintf('Za mało Kondychy na trening: potrzeba %d, masz %d.', $e->cost, $e->available));
        }

        return CharacterResource::fromView(($this->getMyCharacter)(new GetMyCharacterQuery($userId)));
    }
}
