<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Exception\BusinessRuleException;
use App\Http\ApiEnvelope;
use App\Service\ShopBoosters\ShopBoosterSessionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/shop-boosters')]
final class ShopBoosterController extends AbstractController
{
    public function __construct(
        private ShopBoosterSessionService $shopBoosterSessionService,
    ) {
    }

    #[Route('/catalog', name: 'shop_boosters_catalog', methods: ['GET'])]
    public function catalog(): JsonResponse
    {
        return ApiEnvelope::jsonResponse(
            $this->shopBoosterSessionService->getCatalogForApi(),
            'shopBoostersCatalog',
        );
    }

    #[Route('/purchase', name: 'shop_boosters_purchase', methods: ['POST'])]
    public function purchase(#[CurrentUser] User $user, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!\is_array($data)) {
            throw new BusinessRuleException('shopBoosterIdRequired');
        }

        $boosterId = $data['boosterId'] ?? null;
        if (!\is_string($boosterId) || trim($boosterId) === '') {
            throw new BusinessRuleException('shopBoosterIdRequired');
        }

        $this->shopBoosterSessionService->purchase($user, $boosterId);

        return ApiEnvelope::jsonResponse(
            [
                'sessionShopBoosters' => $this->shopBoosterSessionService->buildActiveEntriesForUser($user),
            ],
            'shopBoosterPurchased',
            Response::HTTP_OK,
        );
    }

    #[Route('/prune-expired', name: 'shop_boosters_prune_expired', methods: ['POST'])]
    public function pruneExpired(#[CurrentUser] User $user): JsonResponse
    {
        $this->shopBoosterSessionService->pruneExpiredSessions($user);

        return ApiEnvelope::jsonResponse(
            [
                'sessionShopBoosters' => $this->shopBoosterSessionService->buildActiveEntriesForUser($user),
            ],
            'shopBoostersPruned',
        );
    }
}
