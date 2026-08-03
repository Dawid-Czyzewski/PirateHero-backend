<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Exception\BusinessRuleException;
use App\Http\ApiEnvelope;
use App\Service\Economy\BoosterService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Serializer\SerializerInterface;

#[AsController]
class BoosterController extends AbstractController
{
    public function __construct(
        private BoosterService $boosterService,
        private SerializerInterface $serializer,
    ) {
    }

    public function getAvailableBoosters(#[CurrentUser] User $user): JsonResponse
    {
        $boosters = $this->boosterService->getAvailableBoostersForUser($user);

        return ApiEnvelope::jsonResponseSerialized(
            $this->serializer,
            $boosters,
            null,
            200,
            [],
            ['groups' => ['user:read']],
        );
    }

    public function buyBooster(#[CurrentUser] User $user, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!isset($data['userAvailableBoosterId'])) {
            throw new BusinessRuleException('userAvailableBoosterIdRequired');
        }

        $this->boosterService->buyBooster($user, (int) $data['userAvailableBoosterId']);

        return ApiEnvelope::jsonResponse(['purchased' => true], 'boosterPurchased');
    }

    public function useBooster(#[CurrentUser] User $user, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!isset($data['userBoosterId'])) {
            throw new BusinessRuleException('userBoosterIdRequired');
        }

        $this->boosterService->useBooster($user, (int) $data['userBoosterId']);

        return ApiEnvelope::jsonResponse(['used' => true], 'boosterUsed');
    }
}
