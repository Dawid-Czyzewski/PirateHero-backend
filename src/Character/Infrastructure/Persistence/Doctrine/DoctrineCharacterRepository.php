<?php

declare(strict_types=1);

namespace App\Character\Infrastructure\Persistence\Doctrine;

use App\Character\Domain\Exception\CharacterAlreadyExists;
use App\Character\Domain\Model\Character;
use App\Character\Domain\Model\CharacterId;
use App\Character\Domain\Repository\CharacterRepository;
use App\Shared\Domain\Model\UserId;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Uid\Uuid;

final readonly class DoctrineCharacterRepository implements CharacterRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function nextIdentity(): CharacterId
    {
        return CharacterId::fromString(Uuid::v7()->toRfc4122());
    }

    public function save(Character $character): void
    {
        $this->entityManager->persist($character);

        try {
            $this->entityManager->flush();
        } catch (UniqueConstraintViolationException) {
            throw CharacterAlreadyExists::forUser($character->userId());
        }
    }

    public function ofId(CharacterId $id): ?Character
    {
        return $this->repository()->find($id);
    }

    public function ofUserId(UserId $userId): ?Character
    {
        return $this->repository()->findOneBy(['userId' => $userId]);
    }

    /**
     * @return EntityRepository<Character>
     */
    private function repository(): EntityRepository
    {
        return $this->entityManager->getRepository(Character::class);
    }
}
