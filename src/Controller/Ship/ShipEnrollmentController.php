<?php

declare(strict_types=1);

namespace App\Controller\Ship;

use App\Dto\Ship\InvitationIdInput;
use App\Dto\Ship\RequestIdInput;
use App\Dto\Ship\SetInvitationRequiredInput;
use App\Dto\Ship\ShipIdInput;
use App\Dto\Ship\UserIdInput;
use App\Entity\User;
use App\Exception\OperationForbiddenException;
use App\Http\ApiEnvelope;
use App\Service\Ship\ShipInvitationService;
use App\Service\Ship\ShipJoinRequestService;
use App\Service\Ship\ShipMembershipService;
use App\Service\Ship\ShipQueryService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[AsController]
final class ShipEnrollmentController extends AbstractController
{
    public function __construct(
        private readonly ShipMembershipService $shipMembershipService,
        private readonly ShipInvitationService $shipInvitationService,
        private readonly ShipJoinRequestService $shipJoinRequestService,
        private readonly ShipQueryService $shipQueryService,
        private readonly ShipHttpHelper $shipHttp,
    ) {
    }

    public function setInvitationRequired(#[CurrentUser] User $user, #[MapRequestPayload] SetInvitationRequiredInput $input): JsonResponse
    {
        $ship = $this->shipQueryService->requireShipById($input->shipId);

        if (!$this->shipMembershipService->isUserMember($user, $ship)) {
            throw new OperationForbiddenException('shipMembershipRequired');
        }

        $this->shipMembershipService->setInvitationRequired($ship, $user, (bool) $input->requiresInvitation);

        return ApiEnvelope::jsonResponse(['requiresInvitation' => $ship->getRequiresInvitation()], 'shipInvitationRequirementUpdated');
    }

    public function joinShip(#[CurrentUser] User $user, #[MapRequestPayload] ShipIdInput $input): JsonResponse
    {
        $ship = $this->shipQueryService->requireShipById($input->shipId);
        $member = $this->shipMembershipService->joinShip($ship, $user);

        return ApiEnvelope::jsonResponse(
            $this->shipJoinRequestService->buildJoinShipResponse($member),
            'shipJoined',
        );
    }

    public function requestToJoin(#[CurrentUser] User $user, #[MapRequestPayload] ShipIdInput $input): JsonResponse
    {
        $ship = $this->shipQueryService->requireShipById($input->shipId);
        $joinRequest = $this->shipJoinRequestService->requestToJoin($ship, $user);

        return ApiEnvelope::jsonResponse(
            $this->shipJoinRequestService->buildJoinRequestSentResponse($joinRequest),
            'shipJoinRequestSent',
        );
    }

    public function cancelJoinRequest(#[CurrentUser] User $user, #[MapRequestPayload] ShipIdInput $input): JsonResponse
    {
        $ship = $this->shipQueryService->requireShipById($input->shipId);
        $this->shipJoinRequestService->cancelJoinRequest($ship, $user);

        return ApiEnvelope::jsonResponse(['cancelled' => true], 'shipJoinRequestCancelled');
    }

    public function cancelInvitation(#[CurrentUser] User $user, #[MapRequestPayload] UserIdInput $input): JsonResponse
    {
        $ship = $this->shipHttp->requireCurrentUserShip($user);
        $invitedUser = $this->shipMembershipService->requireUserById($input->userId);
        $this->shipInvitationService->cancelInvitation($ship, $user, $invitedUser);

        return ApiEnvelope::jsonResponse(['cancelled' => true], 'shipInvitationCancelled');
    }

    public function getMyInvitations(#[CurrentUser] User $user): JsonResponse
    {
        return ApiEnvelope::jsonResponse(
            ['invitations' => $this->shipQueryService->getInvitationsData($user)],
            null,
        );
    }

    public function acceptInvitation(#[CurrentUser] User $user, #[MapRequestPayload] InvitationIdInput $input): JsonResponse
    {
        $member = $this->shipInvitationService->acceptInvitation($user, $input->invitationId);

        return ApiEnvelope::jsonResponse(['shipId' => $member->getShip()->getId()], 'shipInvitationAccepted');
    }

    public function declineInvitation(#[CurrentUser] User $user, #[MapRequestPayload] InvitationIdInput $input): JsonResponse
    {
        $this->shipInvitationService->declineInvitation($user, $input->invitationId);

        return ApiEnvelope::jsonResponse(['declined' => true], 'shipInvitationDeclined');
    }

    public function getMyJoinRequests(#[CurrentUser] User $user): JsonResponse
    {
        $ship = $this->shipMembershipService->getShipForUser($user);
        if (!$ship) {
            return ApiEnvelope::jsonResponse(['joinRequests' => []], null);
        }

        $joinRequests = $this->shipJoinRequestService->getJoinRequestsForShip($ship, $user);

        return ApiEnvelope::jsonResponse(['joinRequests' => $joinRequests], null);
    }

    public function approveJoinRequest(#[CurrentUser] User $user, #[MapRequestPayload] RequestIdInput $input): JsonResponse
    {
        $ship = $this->shipHttp->requireCurrentUserShip($user);
        $this->shipJoinRequestService->approveJoinRequest($ship, $user, $input->requestId);

        return ApiEnvelope::jsonResponse(['approved' => true], 'shipJoinRequestApproved');
    }

    public function rejectJoinRequest(#[CurrentUser] User $user, #[MapRequestPayload] RequestIdInput $input): JsonResponse
    {
        $ship = $this->shipHttp->requireCurrentUserShip($user);
        $this->shipJoinRequestService->rejectJoinRequest($ship, $user, $input->requestId);

        return ApiEnvelope::jsonResponse(['rejected' => true], 'shipJoinRequestRejected');
    }
}
