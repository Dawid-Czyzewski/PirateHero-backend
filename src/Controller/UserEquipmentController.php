<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Exception\BusinessRuleException;
use App\Http\ApiEnvelope;
use App\Service\Economy\UserEquipmentService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[AsController]
class UserEquipmentController extends AbstractController
{
    public function __construct(
        private UserEquipmentService $userEquipmentService,
    ) {
    }

    public function equipItem(#[CurrentUser] User $user, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['itemId'])) {
            throw new BusinessRuleException('itemIdRequired');
        }

        $itemId = (int) $data['itemId'];

        $this->userEquipmentService->equipItem($user, $itemId);

        return ApiEnvelope::jsonResponse(['equipped' => true], 'itemEquipped');
    }

    public function unequipItem(#[CurrentUser] User $user, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['slotType'])) {
            throw new BusinessRuleException('slotTypeRequired');
        }

        $this->userEquipmentService->unequipItem($user, (string) $data['slotType']);

        return ApiEnvelope::jsonResponse(['unequipped' => true], 'itemUnequipped');
    }
}
