<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Entity\UserStorage;
use App\Exception\OperationForbiddenException;
use App\Http\ApiEnvelope;
use App\Service\Economy\StorageService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[AsController]
class StorageController extends AbstractController
{
    public function __construct(
        private StorageService $storageService,
    ) {
    }

    public function __invoke(#[CurrentUser] User $user, UserStorage $id, int $fromSlot, int $toSlot): JsonResponse
    {
        if ($id->getUser()->getId() !== $user->getId()) {
            throw new OperationForbiddenException('userOwnershipRequired');
        }

        $this->storageService->moveItemInStorage($user, (int) $fromSlot, (int) $toSlot);

        return ApiEnvelope::jsonResponse(['moved' => true], 'itemMoved');
    }
}
