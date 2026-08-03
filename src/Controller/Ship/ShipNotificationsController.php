<?php

declare(strict_types=1);

namespace App\Controller\Ship;

use App\Dto\Ship\InvitationIdInput;
use App\Dto\Ship\NotificationIdInput;
use App\Dto\Ship\RequestIdInput;
use App\Entity\User;
use App\Http\ApiEnvelope;
use App\Service\Ship\ShipNotificationService;
use App\Service\Ship\ShipQueryService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[AsController]
final class ShipNotificationsController extends AbstractController
{
    public function __construct(
        private readonly ShipNotificationService $shipNotificationService,
        private readonly ShipQueryService $shipQueryService,
    ) {
    }

    public function getUnreadNotificationsCount(#[CurrentUser] User $user): JsonResponse
    {
        return ApiEnvelope::jsonResponse([
            'unreadCount' => $this->shipQueryService->getUnreadNotificationsCount($user),
        ], null);
    }

    public function markInvitationAsRead(#[CurrentUser] User $user, #[MapRequestPayload] InvitationIdInput $input): JsonResponse
    {
        $this->shipNotificationService->markInvitationAsRead($user, $input->invitationId);

        return ApiEnvelope::jsonResponse(['updated' => true], 'invitationMarkedRead');
    }

    public function markJoinRequestAsRead(#[CurrentUser] User $user, #[MapRequestPayload] RequestIdInput $input): JsonResponse
    {
        $this->shipNotificationService->markJoinRequestAsRead($user, $input->requestId);

        return ApiEnvelope::jsonResponse(['updated' => true], 'joinRequestMarkedRead');
    }

    public function getMyRemovalNotifications(#[CurrentUser] User $user): JsonResponse
    {
        return ApiEnvelope::jsonResponse([
            'notifications' => $this->shipQueryService->getRemovalNotifications($user),
        ], null);
    }

    public function markRemovalNotificationAsRead(#[CurrentUser] User $user, #[MapRequestPayload] NotificationIdInput $input): JsonResponse
    {
        $this->shipNotificationService->markRemovalNotificationAsRead($user, $input->notificationId);

        return ApiEnvelope::jsonResponse(['updated' => true], 'removalNotificationMarkedRead');
    }

    public function getMyFightNotifications(#[CurrentUser] User $user): JsonResponse
    {
        return ApiEnvelope::jsonResponse([
            'notifications' => $this->shipQueryService->getFightNotifications($user),
        ], null);
    }

    public function markFightNotificationAsRead(#[CurrentUser] User $user, #[MapRequestPayload] NotificationIdInput $input): JsonResponse
    {
        $this->shipNotificationService->markFightNotificationAsRead($user, $input->notificationId);

        return ApiEnvelope::jsonResponse(['updated' => true], 'fightNotificationMarkedRead');
    }
}
