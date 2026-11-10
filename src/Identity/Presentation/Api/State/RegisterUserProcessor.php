<?php

declare(strict_types=1);

namespace App\Identity\Presentation\Api\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Validator\Exception\ValidationException;
use App\Identity\Application\Command\RegisterUser\RegisterUserCommand;
use App\Identity\Application\Command\RegisterUser\RegisterUserHandler;
use App\Identity\Application\Query\GetUser\GetUserHandler;
use App\Identity\Application\Query\GetUser\GetUserQuery;
use App\Identity\Domain\Exception\InvalidUserData;
use App\Identity\Domain\Exception\UserAlreadyExists;
use App\Identity\Presentation\Api\RegisterUserInput;
use App\Identity\Presentation\Api\UserResource;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

/**
 * @implements ProcessorInterface<RegisterUserInput, UserResource>
 */
final readonly class RegisterUserProcessor implements ProcessorInterface
{
    private const CONFLICT_MESSAGES = [
        'email' => 'This email is already taken.',
        'nick' => 'This nick is already taken.',
    ];

    public function __construct(
        private RegisterUserHandler $registerUser,
        private GetUserHandler $getUser,
        private RequestStack $requestStack,
        #[Autowire(service: 'limiter.register')]
        private RateLimiterFactoryInterface $registerLimiter,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): UserResource
    {
        $this->throttle();

        try {
            $userId = ($this->registerUser)(new RegisterUserCommand($data->email, $data->nick, $data->password));
        } catch (UserAlreadyExists $e) {
            $violations = [] === $e->conflicts
                ? [$this->violation('', $e->getMessage(), $data)]
                : array_map(fn (string $field) => $this->violation($field, self::CONFLICT_MESSAGES[$field], $data), $e->conflicts);

            throw new ValidationException(new ConstraintViolationList($violations));
        } catch (InvalidUserData $e) {
            throw new ValidationException(new ConstraintViolationList([$this->violation($e->field, $e->getMessage(), $data)]));
        }

        return UserResource::fromView(($this->getUser)(new GetUserQuery($userId->toString())));
    }

    private function violation(string $field, string $message, RegisterUserInput $input): ConstraintViolation
    {
        return new ConstraintViolation($message, $message, [], $input, $field, null);
    }

    private function throttle(): void
    {
        $ip = $this->requestStack->getCurrentRequest()?->getClientIp() ?? 'unknown';
        $limit = $this->registerLimiter->create($ip)->consume();

        if (!$limit->isAccepted()) {
            throw new TooManyRequestsHttpException($limit->getRetryAfter()->getTimestamp() - time(), 'Too many registration attempts. Please try again later.');
        }
    }
}
