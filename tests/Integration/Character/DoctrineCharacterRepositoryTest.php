<?php

declare(strict_types=1);

namespace App\Tests\Integration\Character;

use App\Character\Domain\Exception\CharacterAlreadyExists;
use App\Character\Domain\Model\Appearance;
use App\Character\Domain\Model\Character;
use App\Character\Domain\Repository\CharacterRepository;
use App\Shared\Domain\Model\UserId;
use App\Tests\Factory\UserFactory;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

final class DoctrineCharacterRepositoryTest extends KernelTestCase
{
    use Factories;
    use ResetDatabase;

    private CharacterRepository $characters;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->characters = self::getContainer()->get(CharacterRepository::class);
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
    }

    public function testSavedCharacterCanBeReadBackById(): void
    {
        $userId = $this->existingUserId();
        $character = Character::create($this->characters->nextIdentity(), $userId, new Appearance(3, 1, 7, 4, 2));

        $this->characters->save($character);
        $this->entityManager->clear();

        $found = $this->characters->ofId($character->id());

        self::assertInstanceOf(Character::class, $found);
        self::assertNotSame($character, $found);
        self::assertTrue($found->id()->equals($character->id()));
        self::assertTrue($found->userId()->equals($userId));
        self::assertSame(1, $found->level()->value());
        self::assertSame([1, 1, 1, 1], [
            $found->stats()->strength(),
            $found->stats()->endurance(),
            $found->stats()->technique(),
            $found->stats()->charisma(),
        ]);
        self::assertSame([100, 10, 100, 0, 0], [
            $found->wallet()->cebuliony(),
            $found->wallet()->zloteZeby(),
            $found->wallet()->kondycha(),
            $found->wallet()->zasiegi(),
            $found->wallet()->medale(),
        ]);
        self::assertSame([3, 1, 7, 4, 2], [
            $found->appearance()->face(),
            $found->appearance()->hair(),
            $found->appearance()->beard(),
            $found->appearance()->skin(),
            $found->appearance()->tattoo(),
        ]);
        self::assertSame(15, $found->maxHp());
    }

    public function testFindsCharacterByOwner(): void
    {
        $userId = $this->existingUserId();
        $character = Character::create($this->characters->nextIdentity(), $userId, new Appearance(0, 0, 0, 0, 0));
        $this->characters->save($character);
        $this->entityManager->clear();

        $found = $this->characters->ofUserId($userId);

        self::assertInstanceOf(Character::class, $found);
        self::assertTrue($found->id()->equals($character->id()));
    }

    public function testReturnsNullWhenCharacterDoesNotExist(): void
    {
        self::assertNull($this->characters->ofId($this->characters->nextIdentity()));
        self::assertNull($this->characters->ofUserId($this->existingUserId()));
    }

    public function testNextIdentityIsUnique(): void
    {
        self::assertFalse($this->characters->nextIdentity()->equals($this->characters->nextIdentity()));
    }

    public function testAllowsOnlyOneCharacterPerUser(): void
    {
        $userId = $this->existingUserId();
        $this->characters->save(Character::create($this->characters->nextIdentity(), $userId, new Appearance(0, 0, 0, 0, 0)));

        $this->expectException(CharacterAlreadyExists::class);

        $this->characters->save(Character::create($this->characters->nextIdentity(), $userId, new Appearance(1, 1, 1, 1, 1)));
    }

    public function testRequiresExistingUser(): void
    {
        $this->expectException(ForeignKeyConstraintViolationException::class);

        $this->characters->save(Character::create(
            $this->characters->nextIdentity(),
            UserId::fromString('01a1a3ff-0000-7000-8000-000000000000'),
            new Appearance(0, 0, 0, 0, 0),
        ));
    }

    public function testCharacterIsDeletedTogetherWithItsUser(): void
    {
        $user = UserFactory::createOne();
        $character = Character::create($this->characters->nextIdentity(), $user->id(), new Appearance(0, 0, 0, 0, 0));
        $this->characters->save($character);

        $this->entityManager->getConnection()->executeStatement('DELETE FROM users WHERE id = ?', [$user->id()->toString()]);
        $this->entityManager->clear();

        self::assertNull($this->characters->ofId($character->id()));
    }

    private function existingUserId(): UserId
    {
        return UserFactory::createOne()->id();
    }
}
