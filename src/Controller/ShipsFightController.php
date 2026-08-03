<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Exception\BusinessRuleException;
use App\Http\ApiEnvelope;
use App\Service\Combat\ShipsFightService;
use App\Service\Ship\ShipMembershipService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Serializer\SerializerInterface;

#[AsController]
final class ShipsFightController extends AbstractController
{
    public function __construct(
        private readonly ShipsFightService $shipsFightService,
        private readonly ShipMembershipService $shipMembershipService,
        private readonly SerializerInterface $serializer,
    ) {
    }

    public function getOpponents(#[CurrentUser] User $user): JsonResponse
    {
        $ship = $this->shipMembershipService->requireOwnerShip($user);

        return ApiEnvelope::jsonResponseSerialized(
            $this->serializer,
            $this->shipsFightService->getAvailableOpponents($ship),
            null,
            200,
            [],
            ['groups' => ['ship:read']],
        );
    }

    public function canStartFight(#[CurrentUser] User $user): JsonResponse
    {
        $this->shipMembershipService->requireShipOwner($user);

        return ApiEnvelope::jsonResponse([
            'canStart' => $this->shipsFightService->canStartFightToday($user),
        ], null);
    }

    public function startFight(#[CurrentUser] User $user, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!\is_array($data) || !isset($data['opponentShipId'])) {
            throw new BusinessRuleException('opponentShipIdRequired');
        }

        $attackerShip = $this->shipMembershipService->requireOwnerShip($user);
        $result = $this->shipsFightService->startFightByOpponentShipId(
            $attackerShip,
            (int) $data['opponentShipId'],
            $user,
        );

        return ApiEnvelope::jsonResponseSerialized(
            $this->serializer,
            $result,
            null,
            200,
            [],
            ['groups' => ['ship:read']],
        );
    }

    public function getFightHistory(#[CurrentUser] User $user): JsonResponse
    {
        $ship = $this->shipMembershipService->requireShipForUser($user);

        return ApiEnvelope::jsonResponseSerialized(
            $this->serializer,
            $this->shipsFightService->getFightHistory($ship),
            null,
            200,
            [],
            ['groups' => ['ship:read']],
        );
    }

    public function getFightDetails(#[CurrentUser] User $user, int $fightId): JsonResponse
    {
        $ship = $this->shipMembershipService->requireShipForUser($user);

        return ApiEnvelope::jsonResponseSerialized(
            $this->serializer,
            $this->shipsFightService->getFightDetails($fightId, $ship),
            null,
            200,
            [],
            ['groups' => ['ship:read']],
        );
    }
}
