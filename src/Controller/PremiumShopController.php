<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Exception\BusinessRuleException;
use App\Http\ApiEnvelope;
use App\Service\Economy\PremiumShopService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[AsController]
class PremiumShopController extends AbstractController
{
    public function __construct(private PremiumShopService $premiumShopService)
    {
    }

    public function getTransactions(#[CurrentUser] User $user): JsonResponse
    {
        return ApiEnvelope::jsonResponse([
            'transactions' => $this->premiumShopService->getTransactionHistory($user),
        ]);
    }

    public function getCatalog(): JsonResponse
    {
        return ApiEnvelope::jsonResponse($this->premiumShopService->getCatalog());
    }

    public function purchase(#[CurrentUser] User $user, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $packId = isset($data['packId']) ? trim((string) $data['packId']) : '';
        if ($packId === '') {
            throw new BusinessRuleException('premiumPackIdRequired');
        }

        $result = $this->premiumShopService->purchase($user, $packId);

        return ApiEnvelope::jsonResponse($result, 'premiumPackPurchased');
    }
}
