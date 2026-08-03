<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Http\ApiEnvelope;
use App\Service\Economy\DailyRewardService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[AsController]
class DailyRewardController extends AbstractController
{
    public function __construct(private DailyRewardService $dailyRewardService)
    {
    }

    public function getStatus(#[CurrentUser] User $user): JsonResponse
    {
        return ApiEnvelope::jsonResponse($this->dailyRewardService->getStatus($user), null);
    }

    public function claim(#[CurrentUser] User $user): JsonResponse
    {
        $result = $this->dailyRewardService->claim($user);

        return ApiEnvelope::jsonResponse($result, 'dailyRewardClaimed');
    }
}
