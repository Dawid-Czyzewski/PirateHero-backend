<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\Api\Mission\MissionCancelledResponse;
use App\Dto\Api\Mission\MissionStartedResponse;
use App\Entity\User;
use App\Http\ApiEnvelope;
use App\Service\Progression\MissionService;
use App\Service\Progression\QuestService;
use App\Service\Progression\TimedActivityGuard;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[AsController]
final class MissionController extends AbstractController
{
    public function __construct(
        private readonly MissionService $missionService,
        private readonly QuestService $questService,
        private readonly TimedActivityGuard $timedActivityGuard,
    ) {
    }

    public function startMission(#[CurrentUser] User $user, int $id): JsonResponse
    {
        $mission = $this->missionService->resolveOwnedMission($user, $id);
        $this->timedActivityGuard->assertNoOtherMissionInProgress($user, $mission);
        $this->missionService->startMission($user, $mission->getId());

        return ApiEnvelope::jsonResponse((new MissionStartedResponse())->toArray(), 'missionStarted');
    }

    public function cancelMission(#[CurrentUser] User $user, int $id): JsonResponse
    {
        $this->missionService->resolveOwnedMission($user, $id);
        $this->missionService->cancelMission($user);

        return ApiEnvelope::jsonResponse((new MissionCancelledResponse())->toArray(), 'missionCancelled');
    }

    public function completeMission(#[CurrentUser] User $user, int $id): JsonResponse
    {
        $this->missionService->resolveOwnedMission($user, $id);
        $result = $this->missionService->completeMission($user);
        $responseData = $this->questService->mergeQuestPayload(
            $this->missionService->assembleCompletePayload($user, $result),
            $user,
        );

        return ApiEnvelope::jsonResponse(
            $responseData,
            $result['levelData'] !== null ? 'missionCompletedAndLevelUp' : 'missionCompleted'
        );
    }
}
