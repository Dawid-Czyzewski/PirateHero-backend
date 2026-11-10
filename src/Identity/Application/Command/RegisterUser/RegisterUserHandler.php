<?php

declare(strict_types=1);

namespace App\Identity\Application\Command\RegisterUser;

use App\Identity\Domain\Exception\InvalidUserData;
use App\Identity\Domain\Exception\UserAlreadyExists;
use App\Identity\Domain\Model\Email;
use App\Identity\Domain\Model\Nick;
use App\Identity\Domain\Model\PlainPassword;
use App\Identity\Domain\Model\User;
use App\Identity\Domain\Repository\UserRepository;
use App\Identity\Domain\Service\PasswordHasher;
use App\Shared\Domain\Model\UserId;
use Psr\Clock\ClockInterface;

final readonly class RegisterUserHandler
{
    public function __construct(
        private UserRepository $users,
        private PasswordHasher $passwordHasher,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws InvalidUserData
     * @throws UserAlreadyExists
     */
    public function __invoke(RegisterUserCommand $command): UserId
    {
        $email = new Email($command->email);
        $nick = new Nick($command->nick);
        $password = new PlainPassword($command->password);

        $conflicts = [];
        if (null !== $this->users->ofEmail($email)) {
            $conflicts[] = 'email';
        }
        if (null !== $this->users->ofNick($nick)) {
            $conflicts[] = 'nick';
        }
        if ([] !== $conflicts) {
            throw UserAlreadyExists::withConflicts($conflicts);
        }

        $user = User::register(
            $this->users->nextIdentity(),
            $email,
            $nick,
            $this->passwordHasher->hash($password),
            $this->clock->now(),
        );
        $this->users->save($user);

        return $user->id();
    }
}
