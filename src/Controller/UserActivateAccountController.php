<?php

declare(strict_types=1);

namespace App\Controller;

use App\Http\ApiEnvelope;
use App\Service\User\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
class UserActivateAccountController extends AbstractController
{
    public function __construct(
        private UserService $userService,
    ) {
    }

    public function __invoke(string $token): JsonResponse
    {
        $this->userService->activateAccount($token);

        return ApiEnvelope::jsonResponse(['activated' => true], 'accountActivateSuccessfully');
    }
}
