<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Http\ApiEnvelope;
use App\Service\Progression\WeekendTournamentService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[AsController]
final class WeekendTournamentController extends AbstractController
{
    public function __construct(
        private readonly WeekendTournamentService $weekendTournamentService,
    ) {
    }

    public function getStatus(#[CurrentUser] User $user): JsonResponse
    {
        return ApiEnvelope::jsonResponse($this->weekendTournamentService->getStatus($user), null, Response::HTTP_OK);
    }

    public function claim(#[CurrentUser] User $user): JsonResponse
    {
        $result = $this->weekendTournamentService->claim($user);

        return ApiEnvelope::jsonResponse($result, 'weekendTournamentClaimed', Response::HTTP_OK);
    }

    public function getLeaderboard(#[CurrentUser] User $user): JsonResponse
    {
        return ApiEnvelope::jsonResponse(
            ['entries' => $this->weekendTournamentService->getLeaderboard()],
            null,
            Response::HTTP_OK,
        );
    }
}
