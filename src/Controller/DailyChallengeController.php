<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Exception\BusinessRuleException;
use App\Http\ApiEnvelope;
use App\Service\Progression\DailyChallengeService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[AsController]
final class DailyChallengeController extends AbstractController
{
    public function __construct(
        private readonly DailyChallengeService $dailyChallengeService,
    ) {
    }

    public function getStatus(#[CurrentUser] User $user): JsonResponse
    {
        return ApiEnvelope::jsonResponse($this->dailyChallengeService->getStatus($user), null, Response::HTTP_OK);
    }

    public function claimSlot(#[CurrentUser] User $user, Request $request): JsonResponse
    {
        $raw = json_decode($request->getContent(), true);
        if (!\is_array($raw)) {
            throw new BusinessRuleException('dailyChallengeInvalidSlot');
        }

        $slot = $raw['slot'] ?? null;
        if (!\is_int($slot) && !(\is_string($slot) && ctype_digit($slot))) {
            throw new BusinessRuleException('dailyChallengeInvalidSlot');
        }

        $result = $this->dailyChallengeService->claimSlot($user, (int) $slot);

        return ApiEnvelope::jsonResponse($result, 'dailyChallengeClaimed', Response::HTTP_OK);
    }

    public function claimBonus(#[CurrentUser] User $user): JsonResponse
    {
        $result = $this->dailyChallengeService->claimBonus($user);

        return ApiEnvelope::jsonResponse($result, 'dailyChallengeBonusClaimed', Response::HTTP_OK);
    }
}
