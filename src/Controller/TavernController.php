<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Exception\BusinessRuleException;
use App\Http\ApiEnvelope;
use App\Service\Progression\TavernJobService;
use App\Service\Progression\TimedActivityGuard;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[AsController]
final class TavernController extends AbstractController
{
    public function __construct(
        private readonly TavernJobService $tavernJobService,
        private readonly TimedActivityGuard $timedActivityGuard,
    ) {
    }

    public function getStatus(#[CurrentUser] User $user): JsonResponse
    {
        return ApiEnvelope::jsonResponse($this->tavernJobService->getStatus($user), null, Response::HTTP_OK);
    }

    public function startJob(#[CurrentUser] User $user, Request $request): JsonResponse
    {
        $raw = json_decode($request->getContent(), true);
        if (!\is_array($raw)) {
            throw new BusinessRuleException('tavernJobInvalidPayload');
        }

        $jobIdRaw = $raw['jobId'] ?? null;
        if (!\is_int($jobIdRaw) && !(\is_string($jobIdRaw) && ctype_digit($jobIdRaw))) {
            throw new BusinessRuleException('tavernJobInvalidPayload');
        }

        $jobId = (int) $jobIdRaw;
        $this->timedActivityGuard->assertNoTimedActivity($user);
        $status = $this->tavernJobService->start($user, $jobId);

        return ApiEnvelope::jsonResponse($status, 'tavernJobStarted', Response::HTTP_OK);
    }

    public function completeJob(#[CurrentUser] User $user): JsonResponse
    {
        $result = $this->tavernJobService->complete($user);

        return ApiEnvelope::jsonResponse($result, 'tavernJobCompleted', Response::HTTP_OK);
    }

    public function cancelJob(#[CurrentUser] User $user): JsonResponse
    {
        $status = $this->tavernJobService->cancel($user);

        return ApiEnvelope::jsonResponse($status, 'tavernJobCancelled', Response::HTTP_OK);
    }
}
