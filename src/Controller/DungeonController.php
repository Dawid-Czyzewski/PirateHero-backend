<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Exception\BusinessRuleException;
use App\Http\ApiEnvelope;
use App\Service\Dungeon\DungeonService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[AsController]
final class DungeonController extends AbstractController
{
    public function __construct(
        private readonly DungeonService $dungeonService,
    ) {
    }

    public function getProgress(#[CurrentUser] User $user): JsonResponse
    {
        return ApiEnvelope::jsonResponse($this->dungeonService->getProgress($user), null, Response::HTTP_OK);
    }

    public function fightStage(#[CurrentUser] User $user, Request $request): JsonResponse
    {
        $raw = json_decode($request->getContent(), true);
        if (!\is_array($raw)) {
            throw new BusinessRuleException('dungeonInvalidPayload');
        }

        $dungeonId = $raw['dungeonId'] ?? null;
        $stage = $raw['stage'] ?? null;
        if (!\is_string($dungeonId)) {
            throw new BusinessRuleException('dungeonInvalidPayload');
        }
        if (!\is_int($stage) && !(\is_string($stage) && ctype_digit($stage))) {
            throw new BusinessRuleException('dungeonInvalidPayload');
        }

        $result = $this->dungeonService->fightStage($user, $dungeonId, (int) $stage);

        return ApiEnvelope::jsonResponse($result, null, Response::HTTP_OK);
    }
}
