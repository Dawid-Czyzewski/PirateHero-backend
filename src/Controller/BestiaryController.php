<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Http\ApiEnvelope;
use App\Service\Bestiary\BestiaryService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[AsController]
final class BestiaryController extends AbstractController
{
    public function __construct(
        private readonly BestiaryService $bestiaryService,
    ) {
    }

    public function getBestiary(#[CurrentUser] User $user): JsonResponse
    {
        return ApiEnvelope::jsonResponse($this->bestiaryService->getForUser($user), null, Response::HTTP_OK);
    }
}
