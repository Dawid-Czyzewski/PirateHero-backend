<?php

declare(strict_types=1);

namespace App\Identity\Presentation\Api\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Identity\Application\Query\GetUser\GetUserHandler;
use App\Identity\Application\Query\GetUser\GetUserQuery;
use App\Identity\Infrastructure\Security\SecurityUser;
use App\Identity\Presentation\Api\UserResource;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * @implements ProviderInterface<UserResource>
 */
final readonly class MeProvider implements ProviderInterface
{
    public function __construct(
        private Security $security,
        private GetUserHandler $getUser,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): UserResource
    {
        $user = $this->security->getUser();

        if (!$user instanceof SecurityUser) {
            throw new AccessDeniedException();
        }

        return UserResource::fromView(($this->getUser)(new GetUserQuery($user->getId())));
    }
}
