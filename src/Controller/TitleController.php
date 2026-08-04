<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Exception\BusinessRuleException;
use App\Http\ApiEnvelope;
use App\Service\Progression\TitleService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[AsController]
#[Route('/api/user_titles')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class TitleController extends AbstractController
{
    public function __construct(
        private readonly TitleService $titleService,
    ) {
    }

    #[Route('', name: 'api_user_titles', methods: ['GET'])]
    public function listTitles(#[CurrentUser] User $user): JsonResponse
    {
        $result = $this->titleService->getTitlesForUser($user);

        return ApiEnvelope::jsonResponse($result, null);
    }

    #[Route('/equip', name: 'api_user_titles_equip', methods: ['POST'])]
    public function equipTitle(#[CurrentUser] User $user, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!is_array($data) || !isset($data['titleCode']) || !is_string($data['titleCode']) || trim($data['titleCode']) === '') {
            throw new BusinessRuleException('titleCodeRequired');
        }

        $responseData = $this->titleService->equipTitle($user, trim($data['titleCode']));

        return ApiEnvelope::jsonResponse($responseData, 'titleEquipped');
    }

    #[Route('/unequip', name: 'api_user_titles_unequip', methods: ['POST'])]
    public function unequipTitle(#[CurrentUser] User $user): JsonResponse
    {
        $responseData = $this->titleService->unequipTitle($user);

        return ApiEnvelope::jsonResponse($responseData, 'titleUnequipped');
    }
}
