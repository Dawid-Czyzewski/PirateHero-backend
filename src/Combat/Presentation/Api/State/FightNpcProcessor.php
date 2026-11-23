<?php

declare(strict_types=1);

namespace App\Combat\Presentation\Api\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Character\Domain\Exception\CharacterNotFound;
use App\Character\Domain\Exception\InsufficientStamina;
use App\Combat\Application\Command\FightNpc\FightNpcHandler;
use App\Combat\Domain\Exception\NpcNotFound;
use App\Combat\Presentation\Api\FightNpcInput;
use App\Combat\Presentation\Api\FightResource;
use App\Shared\Application\Security\CurrentUser;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * @implements ProcessorInterface<FightNpcInput, FightResource>
 */
final readonly class FightNpcProcessor implements ProcessorInterface
{
    public function __construct(
        private CurrentUser $currentUser,
        private FightNpcHandler $fightNpc,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): FightResource
    {
        $userId = ($this->currentUser->userId() ?? throw new AccessDeniedException())->toString();

        try {
            return FightResource::fromView(($this->fightNpc)($data->toCommand($userId)));
        } catch (CharacterNotFound) {
            throw new NotFoundHttpException('Character not found.');
        } catch (NpcNotFound) {
            throw new NotFoundHttpException('Opponent not found.');
        } catch (InsufficientStamina $e) {
            throw new ConflictHttpException(\sprintf('Za mało Kondychy na walkę: potrzeba %d, masz %d.', $e->cost, $e->available));
        }
    }
}
