<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Enum\RefillType;
use App\Http\ApiEnvelope;
use App\Service\Refill\ResourceRefillService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[AsController]
class FightRefillController extends AbstractController
{
    public function __construct(
        private ResourceRefillService $resourceRefillService,
    ) {
    }

    public function refillFight(#[CurrentUser] User $user): JsonResponse
    {
        return ApiEnvelope::jsonResponse(
            $this->resourceRefillService->refill($user, RefillType::FIGHT),
            null,
        );
    }

    public function getRefillInfo(#[CurrentUser] User $user): JsonResponse
    {
        return ApiEnvelope::jsonResponse(
            $this->resourceRefillService->canRefill($user, RefillType::FIGHT),
            null,
        );
    }
}
