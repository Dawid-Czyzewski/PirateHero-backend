<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Http\ApiEnvelope;
use App\Service\Progression\WeeklyContractService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[AsController]
final class WeeklyContractController extends AbstractController
{
    public function __construct(
        private readonly WeeklyContractService $weeklyContractService,
    ) {
    }

    public function getStatus(#[CurrentUser] User $user): JsonResponse
    {
        return ApiEnvelope::jsonResponse($this->weeklyContractService->getStatus($user), null, Response::HTTP_OK);
    }

    public function claim(#[CurrentUser] User $user): JsonResponse
    {
        $result = $this->weeklyContractService->claim($user);

        return ApiEnvelope::jsonResponse($result, 'weeklyContractClaimed', Response::HTTP_OK);
    }
}
