<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Exception\BusinessRuleException;
use App\Http\ApiEnvelope;
use App\Service\MiniGames\TavernDiceService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/games/tavern/dice')]
final class TavernDiceController extends AbstractController
{
    public function __construct(
        private TavernDiceService $tavernDiceService,
    ) {
    }

    #[Route('/play', name: 'games_tavern_dice_play', methods: ['POST'])]
    public function play(#[CurrentUser] User $user, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!\is_array($data)) {
            throw new BusinessRuleException('tavernDiceInvalidPayload');
        }

        $stakeRaw = $data['stake'] ?? null;
        if (!\is_int($stakeRaw) && !(\is_string($stakeRaw) && ctype_digit($stakeRaw))) {
            throw new BusinessRuleException('tavernDiceStakeInvalid');
        }

        $result = $this->tavernDiceService->play($user, (int) $stakeRaw);

        return ApiEnvelope::jsonResponse($result->toArray(), 'tavernDicePlayed', Response::HTTP_OK);
    }
}
