<?php

declare(strict_types=1);

namespace App\Controller;

use App\Application\Command\User\RegisterUserCommand;
use App\Application\UseCase\User\RegisterUserUseCase;
use App\Dto\UserRegisterDto;
use App\Http\ApiEnvelope;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsController]
final class UserRegisterController extends AbstractController
{
    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator,
        private readonly RegisterUserUseCase $registerUserUseCase,
        #[Autowire('%frontend_url%')]
        private readonly string $frontendUrl,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $content = $request->getContent();
        if ('' === $content) {
            throw new BadRequestHttpException('Request body is required.');
        }

        try {
            $data = $this->serializer->deserialize($content, UserRegisterDto::class, 'json');
        } catch (NotEncodableValueException|UnexpectedValueException $e) {
            throw new BadRequestHttpException('Invalid JSON body.', $e);
        }

        $errors = $this->validator->validate($data);
        if (\count($errors) > 0) {
            throw HttpException::fromStatusCode(
                Response::HTTP_UNPROCESSABLE_ENTITY,
                implode("\n", array_map(static fn ($v) => $v->getMessage(), iterator_to_array($errors))),
                new ValidationFailedException($data, $errors),
            );
        }

        $this->registerUserUseCase->execute(new RegisterUserCommand(
            email: $data->email,
            password: $data->password,
            username: $data->username,
            avatarName: $data->avatarName,
            frontendUrl: $this->frontendUrl,
        ));

        return ApiEnvelope::jsonResponse(['registered' => true], 'userRegisteredSuccessfully', 201);
    }
}
