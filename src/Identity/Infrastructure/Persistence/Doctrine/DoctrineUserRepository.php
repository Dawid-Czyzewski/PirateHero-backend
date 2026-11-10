<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Persistence\Doctrine;

use App\Identity\Domain\Exception\UserAlreadyExists;
use App\Identity\Domain\Model\Email;
use App\Identity\Domain\Model\Nick;
use App\Identity\Domain\Model\User;
use App\Identity\Domain\Repository\UserRepository;
use App\Shared\Domain\Model\UserId;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Uid\Uuid;

final readonly class DoctrineUserRepository implements UserRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function nextIdentity(): UserId
    {
        return UserId::fromString(Uuid::v7()->toRfc4122());
    }

    public function save(User $user): void
    {
        $this->entityManager->persist($user);

        try {
            $this->entityManager->flush();
        } catch (UniqueConstraintViolationException) {
            throw UserAlreadyExists::withConflicts([]);
        }
    }

    public function ofId(UserId $id): ?User
    {
        return $this->repository()->find($id);
    }

    public function ofEmail(Email $email): ?User
    {
        return $this->repository()->findOneBy(['email' => $email]);
    }

    public function ofNick(Nick $nick): ?User
    {
        return $this->repository()->findOneBy(['nick' => $nick]);
    }

    /**
     * @return EntityRepository<User>
     */
    private function repository(): EntityRepository
    {
        return $this->entityManager->getRepository(User::class);
    }
}
