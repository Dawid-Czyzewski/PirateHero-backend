<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use App\Controller\TokenRefreshController;
use App\Dto\RefreshTokenInput;
use Doctrine\ORM\Mapping as ORM;
use Gesdinet\JWTRefreshTokenBundle\Entity\RefreshToken as BaseRefreshToken;

#[ORM\Entity]
#[ApiResource(
    shortName: 'RefreshToken',
    operations: [
        new Post(
            uriTemplate: '/token/refresh',
            controller: TokenRefreshController::class,
            input: RefreshTokenInput::class,
            output: null,
            name: 'api_token_refresh'
        ),
    ],
    paginationEnabled: false
)]
class RefreshToken extends BaseRefreshToken
{
}
