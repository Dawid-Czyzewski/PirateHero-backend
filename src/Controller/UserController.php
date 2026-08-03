<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Http\ApiEnvelope;
use App\Service\User\SimilarUsersResolver;
use App\Service\User\UserProfileAssembler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Serializer\SerializerInterface;

#[AsController]
final class UserController extends AbstractController
{
    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly UserProfileAssembler $userProfileAssembler,
        private readonly SimilarUsersResolver $similarUsersResolver,
    ) {
    }

    public function getUserData(User $user): JsonResponse
    {
        return ApiEnvelope::jsonResponse(
            $this->userProfileAssembler->assembleUserData($user),
            null,
            Response::HTTP_OK,
        );
    }

    public function getSimilarUsers(#[CurrentUser] User $user): JsonResponse
    {
        $similarUsers = $this->similarUsersResolver->findSimilarByAverageSkill($user, 10);

        return ApiEnvelope::jsonResponseSerialized(
            $this->serializer,
            $similarUsers,
            null,
            Response::HTTP_OK,
            [],
            ['groups' => ['user:read']],
        );
    }

    public function getUserPreview(User $user, #[CurrentUser] User $viewer): JsonResponse
    {
        return ApiEnvelope::jsonResponse(
            $this->userProfileAssembler->assembleUserPreview($user, $viewer),
            null,
            Response::HTTP_OK,
        );
    }
}
