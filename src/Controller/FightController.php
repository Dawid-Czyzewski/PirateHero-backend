<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\StartFightInput;
use App\Entity\User;
use App\Http\ApiEnvelope;
use App\Service\Combat\FightService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsController]
final class FightController extends AbstractController
{
    public function __construct(
        private readonly FightService $fightService,
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator,
    ) {
    }

    public function getOpponents(#[CurrentUser] User $user): JsonResponse
    {
        $opponents = $this->fightService->getAvailableOpponents($user);

        return ApiEnvelope::jsonResponseSerialized(
            $this->serializer,
            $opponents,
            null,
            200,
            [],
            ['groups' => ['user:read']],
        );
    }

    public function startFight(#[CurrentUser] User $user, Request $request): JsonResponse
    {
        $content = $request->getContent();
        if ('' === $content) {
            throw new BadRequestHttpException('Request body is required.');
        }
        try {
            $input = $this->serializer->deserialize($content, StartFightInput::class, 'json');
        } catch (NotEncodableValueException $e) {
            throw new BadRequestHttpException('Invalid JSON body.', $e);
        }
        $violations = $this->validator->validate($input);
        if (\count($violations) > 0) {
            throw HttpException::fromStatusCode(
                Response::HTTP_UNPROCESSABLE_ENTITY,
                implode("\n", array_map(static fn ($v) => $v->getMessage(), iterator_to_array($violations))),
                new ValidationFailedException($input, $violations),
            );
        }

        if ($input->opponentId === null || $input->opponentId === '') {
            throw new BadRequestHttpException('Missing opponentId');
        }

        $result = $this->fightService->startFightWithQuestPayloadByOpponentId($user, $input->opponentId);

        return ApiEnvelope::jsonResponseSerialized(
            $this->serializer,
            $result,
            null,
            200,
            [],
            ['groups' => ['user:read']],
        );
    }

    public function getFightHistory(#[CurrentUser] User $user): JsonResponse
    {
        $history = $this->fightService->getFightHistory($user);

        return ApiEnvelope::jsonResponseSerialized(
            $this->serializer,
            $history,
            null,
            200,
            [],
            ['groups' => ['user:read']],
        );
    }

    public function getFightReplay(#[CurrentUser] User $user, int $fightId): JsonResponse
    {
        $replay = $this->fightService->getFightReplayForUser($user, $fightId);

        return ApiEnvelope::jsonResponseSerialized(
            $this->serializer,
            $replay,
            null,
            200,
            [],
            ['groups' => ['user:read']],
        );
    }
}
