<?php

declare(strict_types=1);

namespace App\Identity\Presentation\Api;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use App\Identity\Application\Query\GetUser\UserView;
use App\Identity\Presentation\Api\State\MeProvider;
use App\Identity\Presentation\Api\State\RegisterUserProcessor;

#[ApiResource(
    shortName: 'User',
    operations: [
        new Post(
            uriTemplate: '/auth/register',
            status: 201,
            description: 'Registers a new player.',
            input: RegisterUserInput::class,
            processor: RegisterUserProcessor::class,
        ),
        new Get(
            uriTemplate: '/me',
            description: 'Returns the authenticated player.',
            provider: MeProvider::class,
        ),
    ],
)]
final class UserResource
{
    /**
     * @param list<string> $roles
     */
    public function __construct(
        #[ApiProperty(identifier: true)]
        public string $id,
        public string $email,
        public string $nick,
        public array $roles,
        public \DateTimeImmutable $createdAt,
    ) {
    }

    public static function fromView(UserView $view): self
    {
        return new self($view->id, $view->email, $view->nick, $view->roles, $view->createdAt);
    }
}
