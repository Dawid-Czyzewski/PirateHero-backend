<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Exception\BusinessRuleException;
use App\Http\ApiEnvelope;
use App\Service\Economy\CouponService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[AsController]
class CouponController extends AbstractController
{
    public function __construct(private CouponService $couponService)
    {
    }

    public function redeemCoupon(#[CurrentUser] User $user, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!isset($data['code']) || empty($data['code'])) {
            throw new BusinessRuleException('couponCodeRequired');
        }

        $result = $this->couponService->redeemCoupon($user, trim($data['code']));
        $history = $this->couponService->getUserCouponHistory($user);
        $payload = $result->toArray();
        $payload['history'] = array_map(static fn ($entry) => $entry->toArray(), $history);

        return ApiEnvelope::jsonResponse($payload, 'couponRedeemed');
    }

    public function getCouponHistory(#[CurrentUser] User $user): JsonResponse
    {
        $history = $this->couponService->getUserCouponHistory($user);
        $payload = array_map(static fn ($entry) => $entry->toArray(), $history);

        return ApiEnvelope::jsonResponse(['history' => $payload], null);
    }
}
