<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Enum\UserStatType;
use App\Exception\BusinessRuleException;
use App\Exception\OperationForbiddenException;
use App\Http\ApiEnvelope;
use App\Service\User\SkillPointsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[AsController]
class UserSkillPointsController extends AbstractController
{
    public function __construct(
        private SkillPointsService $skillPointsService,
    ) {
    }

    public function __invoke(#[CurrentUser] User $user, User $id, Request $request): JsonResponse
    {
        if ($id->getId() !== $user->getId()) {
            throw new OperationForbiddenException('userOwnershipRequired');
        }

        $data = json_decode($request->getContent(), true);
        if (!isset($data['stat'])) {
            throw new BusinessRuleException('statRequired');
        }

        try {
            $stat = UserStatType::fromRequest((string) $data['stat']);
        } catch (\ValueError) {
            throw new BusinessRuleException('invalidStatType');
        }

        $this->skillPointsService->addSkillPoint($user, $stat);

        return ApiEnvelope::jsonResponse(['updated' => true], 'skillPointAdded');
    }
}
