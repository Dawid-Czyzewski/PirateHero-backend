<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Http\ApiEnvelope;
use App\Service\Bestiary\BestiaryTrophyService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[AsController]
final class BestiaryTrophyController extends AbstractController
{
    public function __construct(
        private readonly BestiaryTrophyService $bestiaryTrophyService,
    ) {
    }

    public function getStatus(#[CurrentUser] User $user): JsonResponse
    {
        return ApiEnvelope::jsonResponse($this->bestiaryTrophyService->getStatus($user), null, Response::HTTP_OK);
    }

    public function claim(#[CurrentUser] User $user, string $code): JsonResponse
    {
        $result = $this->bestiaryTrophyService->claim($user, $code);

        return ApiEnvelope::jsonResponse($result, 'bestiaryTrophyClaimed', Response::HTTP_OK);
    }
}
