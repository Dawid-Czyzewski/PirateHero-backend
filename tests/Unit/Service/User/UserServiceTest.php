<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\User;

use App\Entity\User;
use App\Exception\ResourceNotFoundException;
use App\Repository\UserRepository;
use App\Service\User\UserService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class UserServiceTest extends TestCase
{
    public function testActivateThrowsForUnknownToken(): void
    {
        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->method('findOneBy')->with(['activateToken' => 'bad'])->willReturn(null);

        $service = new UserService(
            $this->createMock(EntityManagerInterface::class),
            $userRepository,
        );

        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage('invalidActivationToken');
        $service->activateAccount('bad');
    }

    public function testActivateClearsTokenAndFlushes(): void
    {
        $user = new User();

        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->method('findOneBy')->willReturn($user);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('persist')->with($user);
        $em->expects(self::once())->method('flush');

        $service = new UserService($em, $userRepository);
        $service->activateAccount('valid-token');

        self::assertNull($user->getActivateToken());
    }
}
