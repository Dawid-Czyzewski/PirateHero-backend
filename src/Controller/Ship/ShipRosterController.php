<?php

declare(strict_types=1);

namespace App\Controller\Ship;

use App\Dto\Ship\ChangeMemberRoleInput;
use App\Dto\Ship\InviteMemberInput;
use App\Dto\Ship\UserIdInput;
use App\Entity\User;
use App\Enum\ShipRole;
use App\Http\ApiEnvelope;
use App\Service\Ship\ShipInvitationService;
use App\Service\Ship\ShipMembershipService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[AsController]
final class ShipRosterController extends AbstractController
{
    public function __construct(
        private readonly ShipMembershipService $shipMembershipService,
        private readonly ShipInvitationService $shipInvitationService,
        private readonly ShipHttpHelper $shipHttp,
    ) {
    }

    public function inviteMember(#[CurrentUser] User $user, #[MapRequestPayload] InviteMemberInput $input): JsonResponse
    {
        $ship = $this->shipHttp->requireCurrentUserShip($user);
        $invitation = $this->shipInvitationService->inviteMember($ship, $user, trim($input->username));

        return ApiEnvelope::jsonResponse(
            $this->shipInvitationService->buildInvitationSentResponse($invitation),
            'invitationSent',
            201,
        );
    }

    public function removeMember(#[CurrentUser] User $user, #[MapRequestPayload] UserIdInput $input): JsonResponse
    {
        $ship = $this->shipHttp->requireCurrentUserShip($user);
        $userToRemove = $this->shipMembershipService->requireUserById($input->userId);
        $systemMessage = $this->shipMembershipService->removeMember($ship, $user, $userToRemove);

        return ApiEnvelope::jsonResponse(
            $this->shipMembershipService->buildRemoveMemberResponse($ship, $systemMessage),
            'shipMemberRemoved',
        );
    }

    public function leaveShip(#[CurrentUser] User $user): JsonResponse
    {
        $ship = $this->shipHttp->requireCurrentUserShip($user);
        $this->shipMembershipService->leaveShip($ship, $user);

        return ApiEnvelope::jsonResponse(['left' => true], 'shipLeft');
    }

    public function transferOwnership(#[CurrentUser] User $user, #[MapRequestPayload] UserIdInput $input): JsonResponse
    {
        $ship = $this->shipHttp->requireCurrentUserShip($user);
        $newOwner = $this->shipMembershipService->requireUserById($input->userId);
        $this->shipMembershipService->transferOwnership($ship, $user, $newOwner);

        return ApiEnvelope::jsonResponse(['transferred' => true], 'shipOwnershipTransferred');
    }

    public function changeMemberRole(#[CurrentUser] User $user, #[MapRequestPayload] ChangeMemberRoleInput $input): JsonResponse
    {
        $ship = $this->shipHttp->requireCurrentUserShip($user);
        $targetUser = $this->shipMembershipService->requireUserById($input->userId);
        $newRole = ShipRole::from($input->role);
        $this->shipMembershipService->changeMemberRole($ship, $user, $targetUser, $newRole);

        return ApiEnvelope::jsonResponse(
            $this->shipMembershipService->buildMemberRoleChangedResponse($targetUser),
            'shipMemberRoleChanged',
        );
    }

    public function searchUsers(#[CurrentUser] User $user, Request $request): JsonResponse
    {
        $username = $request->query->get('username', '');

        return ApiEnvelope::jsonResponse(
            $this->shipMembershipService->searchUsersForApi((string) $username),
            null,
        );
    }
}
