<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Exception\BusinessRuleException;
use App\Http\ApiEnvelope;
use App\Service\Progression\CompanionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[AsController]
#[Route('/api/users/companion')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class CompanionController extends AbstractController
{
    public function __construct(
        private readonly CompanionService $companionService,
    ) {
    }

    #[Route('/status', name: 'api_users_companion_status', methods: ['GET'])]
    public function status(#[CurrentUser] User $user): JsonResponse
    {
        return ApiEnvelope::jsonResponse($this->companionService->getStatus($user), null);
    }

    #[Route('/feed', name: 'api_users_companion_feed', methods: ['POST'])]
    public function feed(#[CurrentUser] User $user, Request $request): JsonResponse
    {
        $companionCode = $this->requireCompanionCode($request);
        $responseData = $this->companionService->feed($user, $companionCode);

        return ApiEnvelope::jsonResponse($responseData, 'companionFed');
    }

    #[Route('/equip', name: 'api_users_companion_equip', methods: ['POST'])]
    public function equip(#[CurrentUser] User $user, Request $request): JsonResponse
    {
        $companionCode = $this->requireCompanionCode($request);
        $responseData = $this->companionService->equip($user, $companionCode);

        return ApiEnvelope::jsonResponse($responseData, 'companionEquipped');
    }

    #[Route('/unequip', name: 'api_users_companion_unequip', methods: ['POST'])]
    public function unequip(#[CurrentUser] User $user): JsonResponse
    {
        $responseData = $this->companionService->unequip($user);

        return ApiEnvelope::jsonResponse($responseData, 'companionUnequipped');
    }

    #[Route('/ability', name: 'api_users_companion_ability', methods: ['POST'])]
    public function ability(#[CurrentUser] User $user): JsonResponse
    {
        $responseData = $this->companionService->useAbility($user);

        return ApiEnvelope::jsonResponse($responseData, 'companionAbilityUsed');
    }

    private function requireCompanionCode(Request $request): string
    {
        $data = json_decode($request->getContent(), true);
        if (!\is_array($data) || !isset($data['companionCode']) || !\is_string($data['companionCode']) || trim($data['companionCode']) === '') {
            throw new BusinessRuleException('companionCodeRequired');
        }

        return trim($data['companionCode']);
    }
}
