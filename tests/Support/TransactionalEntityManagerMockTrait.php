<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Entity\User;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

trait TransactionalEntityManagerMockTrait
{
    /**
     * @param array{
     *     withConnection?: bool,
     *     repository?: EntityRepository|null,
     *     withPersist?: bool,
     *     withRemove?: bool,
     *     withFlush?: bool,
     * } $options
     */
    protected function mockTransactionalEmForUser(User $user, array $options = []): EntityManagerInterface
    {
        $withConnection = $options['withConnection'] ?? true;
        $repository = $options['repository'] ?? null;
        $withPersist = $options['withPersist'] ?? false;
        $withRemove = $options['withRemove'] ?? false;
        $withFlush = $options['withFlush'] ?? false;

        $em = $this->createMock(EntityManagerInterface::class);

        if ($withConnection) {
            $connection = $this->createMock(Connection::class);
            $connection->method('beginTransaction');
            $connection->method('commit');
            $connection->method('rollBack');
            $em->method('getConnection')->willReturn($connection);
        }

        $em->method('find')->willReturnCallback(
            static function (string $class, mixed $id, ?int $lockMode = null) use ($user) {
                if ($class === User::class && $lockMode === LockMode::PESSIMISTIC_WRITE) {
                    return $user;
                }

                return null;
            }
        );

        if ($repository !== null) {
            $em->method('getRepository')->willReturn($repository);
        }

        if ($withPersist) {
            $em->method('persist');
        }

        if ($withRemove) {
            $em->method('remove');
        }

        if ($withFlush) {
            $em->method('flush');
        }

        return $em;
    }
}
