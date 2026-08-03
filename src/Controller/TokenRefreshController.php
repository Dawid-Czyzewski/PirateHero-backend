<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\RefreshTokenInput;
use App\Http\ApiEnvelope;
use App\Service\User\TokenRefreshService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;

#[AsController]
final class TokenRefreshController extends AbstractController
{
    public function __construct(
        private readonly TokenRefreshService $tokenRefreshService,
    ) {
    }

    public function __invoke(#[MapRequestPayload] RefreshTokenInput $data): JsonResponse
    {
        return ApiEnvelope::jsonResponse($this->tokenRefreshService->refresh($data->refreshToken), null);
    }
}
