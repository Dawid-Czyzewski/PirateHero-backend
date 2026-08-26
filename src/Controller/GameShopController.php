<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Http\ApiEnvelope;
use App\Service\Economy\UserEquipmentService;
use App\Service\Economy\UserStoreService;
use App\Service\Economy\WearableItemUpgradeService;
use App\Service\GameShop\GameShopService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/game-shop')]
final class GameShopController extends AbstractController
{
    public function __construct(
        private readonly GameShopService $gameShopService,
        private readonly UserStoreService $userStoreService,
        private readonly UserEquipmentService $userEquipmentService,
        private readonly WearableItemUpgradeService $wearableItemUpgradeService,
    ) {
    }

    #[Route('/state', name: 'game_shop_state', methods: ['GET'])]
    public function state(#[CurrentUser] User $user): JsonResponse
    {
        $this->userStoreService->ensureUserStore($user);
        $this->userEquipmentService->ensureUserEquipment($user);

        return ApiEnvelope::jsonResponse($this->gameShopService->buildState($user), 'gameShopState');
    }

    #[Route('/purchase', name: 'game_shop_purchase', methods: ['POST'])]
    public function purchase(#[CurrentUser] User $user, Request $request): JsonResponse
    {
        $data = $this->gameShopService->decodeJsonBody($request->getContent());
        $storeSlotId = $this->gameShopService->requireBodyInt($data, 'storeSlotId', 'storeSlotIdRequired');
        $chest = $this->gameShopService->optionalBodyInt($data, 'chestSlotIndex');

        $this->userStoreService->ensureUserStore($user);
        $this->userStoreService->buyItem($user, $storeSlotId, $chest);

        $payload = $this->gameShopService->buildStateWithQuests((string) $user->getId());
        if ($payload === null) {
            return ApiEnvelope::jsonResponse([], 'itemPurchased', Response::HTTP_OK);
        }

        return ApiEnvelope::jsonResponse($payload, 'itemPurchased');
    }

    #[Route('/sell', name: 'game_shop_sell', methods: ['POST'])]
    public function sell(#[CurrentUser] User $user, Request $request): JsonResponse
    {
        $data = $this->gameShopService->decodeJsonBody($request->getContent());
        $storageSlotId = $this->gameShopService->requireBodyInt($data, 'storageSlotId', 'storageSlotIdRequired');

        $this->userStoreService->sellItem($user, $storageSlotId);

        $payload = $this->gameShopService->buildStateWithQuests((string) $user->getId());
        if ($payload === null) {
            return ApiEnvelope::jsonResponse([], 'itemSold');
        }

        return ApiEnvelope::jsonResponse($payload, 'itemSold');
    }

    #[Route('/sell-equipped', name: 'game_shop_sell_equipped', methods: ['POST'])]
    public function sellEquipped(#[CurrentUser] User $user, Request $request): JsonResponse
    {
        $data = $this->gameShopService->decodeJsonBody($request->getContent());
        $slotType = $this->gameShopService->requireBodyString($data, 'slotType', 'slotTypeRequired');

        $this->userEquipmentService->sellEquippedItem($user, $slotType);

        $payload = $this->gameShopService->buildStateWithQuests((string) $user->getId());
        if ($payload === null) {
            return ApiEnvelope::jsonResponse([], 'itemSold');
        }

        return ApiEnvelope::jsonResponse($payload, 'itemSold');
    }

    #[Route('/equip', name: 'game_shop_equip', methods: ['POST'])]
    public function equip(#[CurrentUser] User $user, Request $request): JsonResponse
    {
        $data = $this->gameShopService->decodeJsonBody($request->getContent());
        $itemId = $this->gameShopService->requireBodyInt($data, 'itemId', 'itemIdRequired');

        $this->userEquipmentService->equipItem($user, $itemId);

        $payload = $this->gameShopService->buildFreshState((string) $user->getId());
        if ($payload === null) {
            return ApiEnvelope::jsonResponse(['equipped' => true], 'itemEquipped');
        }

        return ApiEnvelope::jsonResponse($payload, 'itemEquipped');
    }

    #[Route('/refresh', name: 'game_shop_refresh', methods: ['POST'])]
    public function refresh(#[CurrentUser] User $user): JsonResponse
    {
        $this->userStoreService->ensureUserStore($user);
        $this->userStoreService->refreshStore($user);

        $payload = $this->gameShopService->buildFreshState((string) $user->getId());
        if ($payload === null) {
            return ApiEnvelope::jsonResponse(['refreshed' => true], 'storeRefreshed');
        }

        return ApiEnvelope::jsonResponse($payload, 'storeRefreshed');
    }

    #[Route('/upgrade', name: 'game_shop_upgrade', methods: ['POST'])]
    public function upgrade(#[CurrentUser] User $user, Request $request): JsonResponse
    {
        $data = $this->gameShopService->decodeJsonBody($request->getContent());
        $itemId = $this->gameShopService->requireBodyInt($data, 'itemId', 'itemIdRequired');

        $result = $this->wearableItemUpgradeService->upgrade($user, $itemId);

        $payload = $this->gameShopService->buildFreshState((string) $user->getId());
        if ($payload === null) {
            return ApiEnvelope::jsonResponse($result, 'itemUpgraded');
        }

        return ApiEnvelope::jsonResponse(array_merge($payload, ['upgrade' => $result]), 'itemUpgraded');
    }

    #[Route('/specialize', name: 'game_shop_specialize', methods: ['POST'])]
    public function specialize(#[CurrentUser] User $user, Request $request): JsonResponse
    {
        $data = $this->gameShopService->decodeJsonBody($request->getContent());
        $itemId = $this->gameShopService->requireBodyInt($data, 'itemId', 'itemIdRequired');
        $specialization = $this->gameShopService->requireBodyString($data, 'specialization', 'specializationRequired');

        $result = $this->wearableItemUpgradeService->specialize($user, $itemId, $specialization);

        $payload = $this->gameShopService->buildFreshState((string) $user->getId());
        if ($payload === null) {
            return ApiEnvelope::jsonResponse($result, 'itemSpecialized');
        }

        return ApiEnvelope::jsonResponse(array_merge($payload, ['specialize' => $result]), 'itemSpecialized');
    }
}
