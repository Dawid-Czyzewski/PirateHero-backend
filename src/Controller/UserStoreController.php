<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Exception\BusinessRuleException;
use App\Exception\OperationForbiddenException;
use App\Http\ApiEnvelope;
use App\Service\Economy\UserStoreService;
use App\Service\Progression\QuestService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Serializer\SerializerInterface;

#[AsController]
class UserStoreController extends AbstractController
{
    public function __construct(
        private UserStoreService $userStoreService,
        private QuestService $questService,
        private SerializerInterface $serializer,
    ) {
    }

    public function getByUserId(string $id, #[CurrentUser] User $currentUser): JsonResponse
    {
        if ((string) $currentUser->getId() !== $id) {
            throw new OperationForbiddenException('storeAccessDenied');
        }

        $store = $this->userStoreService->getStoreByUserId($id);

        return ApiEnvelope::jsonResponseSerialized(
            $this->serializer,
            $store,
            null,
            Response::HTTP_OK,
            [],
            ['groups' => ['user:read']],
        );
    }

    public function buyItem(#[CurrentUser] User $user, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!isset($data['storeSlotId'])) {
            throw new BusinessRuleException('storeSlotIdRequired');
        }

        $chestSlotIndex = null;
        if (array_key_exists('chestSlotIndex', $data) && $data['chestSlotIndex'] !== null) {
            $chestSlotIndex = (int) $data['chestSlotIndex'];
        }

        $this->userStoreService->buyItem($user, (int) $data['storeSlotId'], $chestSlotIndex);

        $questsResponse = $this->questService->formatQuestsResponse($user);

        return ApiEnvelope::jsonResponse([
            'quests' => $questsResponse['quests'],
            'hasUnclaimedRewards' => $questsResponse['hasUnclaimedRewards'],
            'unclaimedCount' => $questsResponse['unclaimedCount'],
        ], 'itemPurchased');
    }

    public function sellItem(#[CurrentUser] User $user, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!isset($data['storageSlotId'])) {
            throw new BusinessRuleException('storageSlotIdRequired');
        }

        $this->userStoreService->sellItem($user, (int) $data['storageSlotId']);

        $questsResponse = $this->questService->formatQuestsResponse($user);

        return ApiEnvelope::jsonResponse([
            'quests' => $questsResponse['quests'],
            'hasUnclaimedRewards' => $questsResponse['hasUnclaimedRewards'],
            'unclaimedCount' => $questsResponse['unclaimedCount'],
        ], 'itemSold');
    }

    public function refreshStore(#[CurrentUser] User $user, Request $request): JsonResponse
    {
        $this->userStoreService->refreshStore($user);

        return ApiEnvelope::jsonResponse(['refreshed' => true], 'storeRefreshed');
    }
}
