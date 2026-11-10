<?php

declare(strict_types=1);

namespace App\Character\Presentation\Api\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Validator\Exception\ValidationException;
use App\Character\Application\Command\CreateCharacter\CreateCharacterHandler;
use App\Character\Application\Query\GetMyCharacter\GetMyCharacterHandler;
use App\Character\Application\Query\GetMyCharacter\GetMyCharacterQuery;
use App\Character\Domain\Exception\CharacterAlreadyExists;
use App\Character\Domain\Exception\InvalidCharacterData;
use App\Character\Presentation\Api\CharacterResource;
use App\Character\Presentation\Api\CreateCharacterInput;
use App\Shared\Application\Security\CurrentUser;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

/**
 * @implements ProcessorInterface<CreateCharacterInput, CharacterResource>
 */
final readonly class CreateCharacterProcessor implements ProcessorInterface
{
    public function __construct(
        private CurrentUser $currentUser,
        private CreateCharacterHandler $createCharacter,
        private GetMyCharacterHandler $getMyCharacter,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): CharacterResource
    {
        $userId = $this->currentUser->userId() ?? throw new AccessDeniedException();

        try {
            ($this->createCharacter)($data->toCommand($userId->toString()));
        } catch (CharacterAlreadyExists) {
            throw new ConflictHttpException('Character already exists.');
        } catch (InvalidCharacterData $e) {
            throw new ValidationException(new ConstraintViolationList([new ConstraintViolation($e->getMessage(), $e->getMessage(), [], $data, $e->field, null)]));
        }

        return CharacterResource::fromView(($this->getMyCharacter)(new GetMyCharacterQuery($userId->toString())));
    }
}
