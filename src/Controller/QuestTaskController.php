<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Http\ApiEnvelope;
use App\Service\Progression\QuestService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[AsController]
class QuestTaskController extends AbstractController
{
    public function __construct(
        private QuestService $questService,
    ) {
    }

    public function getUserQuests(#[CurrentUser] User $user): JsonResponse
    {
        $result = $this->questService->getUserQuests($user);

        return ApiEnvelope::jsonResponse($result, null);
    }

    public function claimReward(#[CurrentUser] User $user, int $id): JsonResponse
    {
        $responseData = $this->questService->claimReward($user, $id);

        return ApiEnvelope::jsonResponse($responseData, null);
    }
}
