<?php

declare(strict_types=1);

namespace App\Controller\Ship;

use App\Dto\Ship\DepositShipInput;
use App\Dto\Ship\UpgradeShipInput;
use App\Entity\User;
use App\Exception\OperationForbiddenException;
use App\Http\ApiEnvelope;
use App\Service\Ship\ShipEconomyService;
use App\Service\Ship\ShipMembershipService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[AsController]
final class ShipEconomyController extends AbstractController
{
    public function __construct(
        private readonly ShipEconomyService $shipEconomyService,
        private readonly ShipMembershipService $shipMembershipService,
        private readonly ShipHttpHelper $shipHttp,
    ) {
    }

    public function depositToShip(#[CurrentUser] User $user, #[MapRequestPayload] DepositShipInput $input): JsonResponse
    {
        $ship = $this->shipHttp->requireCurrentUserShip($user);
        $systemMessage = $this->shipEconomyService->depositToShip($ship, $user, $input->gold, $input->diamonds);

        return ApiEnvelope::jsonResponse(
            $this->shipEconomyService->buildDepositResponse($ship, $user, $systemMessage),
            'shipDepositSuccessful',
        );
    }

    public function upgradeShip(#[CurrentUser] User $user, #[MapRequestPayload] UpgradeShipInput $input): JsonResponse
    {
        $ship = $this->shipHttp->requireCurrentUserShip($user);
        if (!$this->shipMembershipService->isUserOwner($user, $ship)) {
            throw new OperationForbiddenException('shipOwnerRequired');
        }

        $result = $this->shipEconomyService->upgradeShip($ship, $input->upgradeType);

        return ApiEnvelope::jsonResponse(
            $this->shipEconomyService->buildUpgradeResponse($ship, $result),
            'shipUpgradeSuccessful',
        );
    }
}
