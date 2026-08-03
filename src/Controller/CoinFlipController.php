<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Enum\CoinFlipSide;
use App\Exception\BusinessRuleException;
use App\Http\ApiEnvelope;
use App\Service\MiniGames\CoinFlipService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/games/coin-flip')]
final class CoinFlipController extends AbstractController
{
    public function __construct(
        private CoinFlipService $coinFlipService,
    ) {
    }

    #[Route('/play', name: 'games_coin_flip_play', methods: ['POST'])]
    public function play(#[CurrentUser] User $user, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!\is_array($data)) {
            throw new BusinessRuleException('coinFlipInvalidPayload');
        }

        $stakeRaw = $data['stake'] ?? null;
        if (!\is_int($stakeRaw)) {
            throw new BusinessRuleException('coinFlipStakeInvalid');
        }

        $choiceRaw = $data['choice'] ?? null;
        if (!\is_string($choiceRaw)) {
            throw new BusinessRuleException('coinFlipChoiceInvalid');
        }

        $choice = CoinFlipSide::tryFrom($choiceRaw);
        if ($choice === null) {
            throw new BusinessRuleException('coinFlipChoiceInvalid');
        }

        $result = $this->coinFlipService->play($user, $stakeRaw, $choice);

        return ApiEnvelope::jsonResponse($result->toArray(), 'coinFlipPlayed', Response::HTTP_OK);
    }
}
