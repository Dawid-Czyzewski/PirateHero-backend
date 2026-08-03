<?php

declare(strict_types=1);

namespace App\Controller;

use App\Exception\BusinessRuleException;
use App\Http\ApiEnvelope;
use App\Service\User\CompletePasswordResetService;
use App\Service\User\RequestPasswordResetService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/password-reset')]
final class PasswordResetController extends AbstractController
{
    public function __construct(
        private RequestPasswordResetService $requestPasswordResetService,
        private CompletePasswordResetService $completePasswordResetService,
    ) {
    }

    #[Route('/request', name: 'password_reset_request', methods: ['POST'])]
    public function request(Request $request): JsonResponse
    {
        $raw = json_decode($request->getContent(), true);
        if (!\is_array($raw)) {
            throw new BusinessRuleException('passwordResetInvalidPayload');
        }

        $email = $raw['email'] ?? null;
        if (!\is_string($email)) {
            throw new BusinessRuleException('passwordResetInvalidPayload');
        }

        $this->requestPasswordResetService->requestReset($email);

        return ApiEnvelope::jsonResponse(
            ['sent' => true],
            'passwordResetRequestSent',
            Response::HTTP_OK,
        );
    }

    #[Route('/complete', name: 'password_reset_complete', methods: ['POST'])]
    public function complete(Request $request): JsonResponse
    {
        $raw = json_decode($request->getContent(), true);
        if (!\is_array($raw)) {
            throw new BusinessRuleException('passwordResetInvalidPayload');
        }

        $token = $raw['token'] ?? null;
        $newPassword = $raw['newPassword'] ?? null;
        $newPasswordRepeat = $raw['newPasswordRepeat'] ?? null;

        if (!\is_string($token) || !\is_string($newPassword) || !\is_string($newPasswordRepeat)) {
            throw new BusinessRuleException('passwordResetInvalidPayload');
        }

        $this->completePasswordResetService->completeReset($token, $newPassword, $newPasswordRepeat);

        return ApiEnvelope::jsonResponse(
            ['reset' => true],
            'passwordResetCompleted',
            Response::HTTP_OK,
        );
    }
}
