<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\Api\Work\WorkCancelledResponse;
use App\Dto\Api\Work\WorkStartedResponse;
use App\Entity\User;
use App\Http\ApiEnvelope;
use App\Mapper\Api\WorkMapper;
use App\Service\Progression\TimedActivityGuard;
use App\Service\Progression\WorkService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[AsController]
final class WorkController extends AbstractController
{
    public function __construct(
        private readonly WorkService $workService,
        private readonly TimedActivityGuard $timedActivityGuard,
    ) {
    }

    public function startWork(#[CurrentUser] User $user, int $id): JsonResponse
    {
        $work = $this->workService->resolveOwnedWork($user, $id);
        $this->timedActivityGuard->assertNoOtherWorkInProgress($user, $work);
        $this->workService->startWork($user, $work);

        return ApiEnvelope::jsonResponse((new WorkStartedResponse())->toArray(), 'workStarted');
    }

    public function cancelWork(#[CurrentUser] User $user, int $id): JsonResponse
    {
        $this->workService->resolveOwnedWork($user, $id);
        $this->workService->cancelWork($user);

        return ApiEnvelope::jsonResponse((new WorkCancelledResponse())->toArray(), 'workCancelled');
    }

    public function completeWork(#[CurrentUser] User $user, int $id): JsonResponse
    {
        $this->workService->resolveOwnedWork($user, $id);
        $result = $this->workService->completeWork($user);

        return ApiEnvelope::jsonResponse(
            WorkMapper::completeResponse(
                $result['earnedGold'],
                $result['bonusPercent'],
                $this->workService->formatWorkDtosForUser($user),
            )->toArray(),
            'workCompleted',
        );
    }
}
