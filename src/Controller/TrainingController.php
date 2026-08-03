<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\Api\Training\TrainingCancelledResponse;
use App\Dto\Api\Training\TrainingStartedResponse;
use App\Entity\User;
use App\Http\ApiEnvelope;
use App\Mapper\Api\TrainingMapper;
use App\Service\Progression\TimedActivityGuard;
use App\Service\Progression\TrainingService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[AsController]
final class TrainingController extends AbstractController
{
    public function __construct(
        private readonly TrainingService $trainingService,
        private readonly TimedActivityGuard $timedActivityGuard,
    ) {
    }

    public function startTraining(#[CurrentUser] User $user, int $id): JsonResponse
    {
        $training = $this->trainingService->resolveOwnedTraining($user, $id);
        $this->timedActivityGuard->assertNoOtherTrainingInProgress($user, $training);
        $this->trainingService->startTraining($user, $training);

        return ApiEnvelope::jsonResponse((new TrainingStartedResponse())->toArray(), 'trainingStarted');
    }

    public function cancelTraining(#[CurrentUser] User $user, int $id): JsonResponse
    {
        $this->trainingService->resolveOwnedTraining($user, $id);
        $this->trainingService->cancelTraining($user);

        return ApiEnvelope::jsonResponse((new TrainingCancelledResponse())->toArray(), 'trainingCancelled');
    }

    public function completeTraining(#[CurrentUser] User $user, int $id): JsonResponse
    {
        $this->trainingService->resolveOwnedTraining($user, $id);
        $this->trainingService->completeTraining($user);

        return ApiEnvelope::jsonResponse(
            TrainingMapper::listResponse($this->trainingService->formatTrainingDtosForUser($user))->toArray(),
            'trainingCompleted',
        );
    }
}
