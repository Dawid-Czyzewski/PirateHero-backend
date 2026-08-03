<?php

declare(strict_types=1);

namespace App\Controller\Ship;

use App\Dto\CreateShipInput;
use App\Dto\Ship\UpdateShipInput;
use App\Entity\User;
use App\Exception\OperationForbiddenException;
use App\Http\ApiEnvelope;
use App\Service\Ship\ShipMembershipService;
use App\Service\Ship\ShipQueryService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[AsController]
final class ShipOverviewController extends AbstractController
{
    public function __construct(
        private readonly ShipMembershipService $shipMembershipService,
        private readonly ShipQueryService $shipQueryService,
        private readonly ShipHttpHelper $shipHttp,
    ) {
    }

    public function getMyShip(#[CurrentUser] User $user): JsonResponse
    {
        $data = $this->shipQueryService->getMyShipData($user);
        if (!$data) {
            return ApiEnvelope::jsonResponse(['ship' => null], null);
        }

        return ApiEnvelope::jsonResponse($data, null);
    }

    public function createShip(#[CurrentUser] User $user, #[MapRequestPayload] CreateShipInput $input): JsonResponse
    {
        $ship = $this->shipMembershipService->createShip(
            $user,
            trim($input->title),
            $input->description
        );

        return ApiEnvelope::jsonResponse(
            ['ship' => $this->shipQueryService->toShipSummaryArray($ship)],
            'shipCreated',
            201,
        );
    }

    public function updateShip(#[CurrentUser] User $user, #[MapRequestPayload] UpdateShipInput $input): JsonResponse
    {
        $ship = $this->shipHttp->requireCurrentUserShip($user);
        if (!$this->shipMembershipService->isUserOwner($user, $ship)) {
            throw new OperationForbiddenException('shipOwnerRequired');
        }

        $title = null !== $input->title ? trim($input->title) : null;
        if ('' === $title) {
            $title = null;
        }

        $systemMessage = $this->shipMembershipService->updateShip(
            $ship,
            $user,
            $title,
            $input->description,
            $input->internalNotes,
        );

        return ApiEnvelope::jsonResponse(
            $this->shipQueryService->buildUpdateResponse($ship, $systemMessage),
            'shipUpdated',
        );
    }

    public function deleteShip(#[CurrentUser] User $user): JsonResponse
    {
        $ship = $this->shipHttp->requireCurrentUserShip($user);
        $this->shipMembershipService->deleteShip($ship, $user);

        return ApiEnvelope::jsonResponse(['deleted' => true], 'shipDeleted');
    }

    public function getShipPreview(#[CurrentUser] User $user, int $id): JsonResponse
    {
        return ApiEnvelope::jsonResponse(
            $this->shipQueryService->getShipPreviewById($id, $user),
            null,
        );
    }
}
