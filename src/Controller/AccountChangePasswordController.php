<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Exception\BusinessRuleException;
use App\Http\ApiEnvelope;
use App\Service\User\ChangeUserPasswordService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/account')]
final class AccountChangePasswordController extends AbstractController
{
    public function __construct(
        private ChangeUserPasswordService $changeUserPasswordService,
    ) {
    }

    #[Route('/change-password', name: 'account_change_password', methods: ['POST'])]
    public function changePassword(#[CurrentUser] User $user, Request $request): JsonResponse
    {
        $raw = json_decode($request->getContent(), true);
        if (!\is_array($raw)) {
            throw new BusinessRuleException('changePasswordInvalidPayload');
        }

        $current = $raw['currentPassword'] ?? null;
        $new = $raw['newPassword'] ?? null;
        $repeat = $raw['newPasswordRepeat'] ?? null;

        if (!\is_string($current) || !\is_string($new) || !\is_string($repeat)) {
            throw new BusinessRuleException('changePasswordInvalidPayload');
        }

        $this->changeUserPasswordService->changePassword($user, $current, $new, $repeat);

        return ApiEnvelope::jsonResponse(['changed' => true], 'passwordChangedSuccessfully', Response::HTTP_OK);
    }
}
