<?php

declare(strict_types=1);

namespace App\Controller\Ship;

use App\Entity\User;
use App\Exception\BusinessRuleException;
use App\Http\ApiEnvelope;
use App\Service\Ship\ShipVoyageService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[AsController]
final class ShipVoyageController extends AbstractController
{
    public function __construct(
        private readonly ShipVoyageService $shipVoyageService,
    ) {
    }

    public function getStatus(#[CurrentUser] User $user): JsonResponse
    {
        return ApiEnvelope::jsonResponse($this->shipVoyageService->getStatus($user), null, Response::HTTP_OK);
    }

    public function start(#[CurrentUser] User $user, Request $request): JsonResponse
    {
        $raw = json_decode($request->getContent(), true);
        if (!\is_array($raw)) {
            throw new BusinessRuleException('shipVoyageInvalidDuration');
        }
        $duration = $raw['durationSeconds'] ?? null;
        if (!\is_int($duration) && !(\is_string($duration) && ctype_digit($duration))) {
            throw new BusinessRuleException('shipVoyageInvalidDuration');
        }

        $status = $this->shipVoyageService->start($user, (int) $duration);

        return ApiEnvelope::jsonResponse($status, 'shipVoyageStarted', Response::HTTP_OK);
    }

    public function complete(#[CurrentUser] User $user): JsonResponse
    {
        $result = $this->shipVoyageService->complete($user);

        return ApiEnvelope::jsonResponse($result, 'shipVoyageCompleted', Response::HTTP_OK);
    }

    public function cancel(#[CurrentUser] User $user): JsonResponse
    {
        $result = $this->shipVoyageService->cancel($user);

        return ApiEnvelope::jsonResponse($result, 'shipVoyageCancelled', Response::HTTP_OK);
    }
}
