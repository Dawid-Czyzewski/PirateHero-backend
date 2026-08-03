<?php

declare(strict_types=1);

namespace App\Controller;

use App\Http\ApiEnvelope;
use App\Service\User\RankingService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
class RankingController extends AbstractController
{
    public function __construct(
        private RankingService $rankingService,
    ) {
    }

    public function getPlayersRanking(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = max(1, min(50, (int) $request->query->get('limit', 20)));
        $sortBy = $request->query->get('sortBy', 'famePoints');
        $sortOrder = $request->query->get('sortOrder', 'DESC');

        $search = $this->normalizeSearchQuery($request->query->get('search'));

        $payload = $this->rankingService->getPlayersRanking($page, $limit, (string) $sortBy, (string) $sortOrder, $search);

        return ApiEnvelope::jsonResponse($payload->toArray(), null);
    }

    public function getShipsRanking(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = max(1, min(50, (int) $request->query->get('limit', 20)));
        $sortBy = $request->query->get('sortBy', 'totalFamePoints');
        $sortOrder = $request->query->get('sortOrder', 'DESC');
        $search = $this->normalizeSearchQuery($request->query->get('search'));

        $payload = $this->rankingService->getShipsRanking($page, $limit, (string) $sortBy, (string) $sortOrder, $search);

        return ApiEnvelope::jsonResponse($payload->toArray(), null);
    }

    private function normalizeSearchQuery(mixed $raw): ?string
    {
        if (!is_string($raw)) {
            return null;
        }
        $trimmed = trim($raw);
        if ($trimmed === '') {
            return null;
        }

        return mb_substr($trimmed, 0, 64);
    }
}
