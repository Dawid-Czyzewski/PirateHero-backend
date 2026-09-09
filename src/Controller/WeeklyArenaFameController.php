<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Exception\BusinessRuleException;
use App\Http\ApiEnvelope;
use App\Service\Progression\WeeklyArenaFameService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[AsController]
final class WeeklyArenaFameController extends AbstractController
{
    public function __construct(
        private readonly WeeklyArenaFameService $weeklyArenaFameService,
    ) {
    }

    public function getStatus(#[CurrentUser] User $user): JsonResponse
    {
        return ApiEnvelope::jsonResponse($this->weeklyArenaFameService->getStatus($user), null, Response::HTTP_OK);
    }

    public function claim(#[CurrentUser] User $user, Request $request): JsonResponse
    {
        $raw = json_decode($request->getContent(), true);
        if (!\is_array($raw)) {
            throw new BusinessRuleException('weeklyArenaFameInvalidTier');
        }

        $tier = $raw['tier'] ?? null;
        if (!\is_int($tier) && !(\is_string($tier) && ctype_digit($tier))) {
            throw new BusinessRuleException('weeklyArenaFameInvalidTier');
        }

        $result = $this->weeklyArenaFameService->claimTier($user, (int) $tier);

        return ApiEnvelope::jsonResponse($result, 'weeklyArenaFameClaimed', Response::HTTP_OK);
    }
}
