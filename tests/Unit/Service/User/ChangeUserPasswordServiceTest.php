<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\User;

use App\Entity\User;
use App\Exception\BusinessRuleException;
use App\Service\User\ChangeUserPasswordService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class ChangeUserPasswordServiceTest extends TestCase
{
    public function testSuccessUpdatesPasswordAndFlushes(): void
    {
        $user = new User();
        $user->setEmail('p@example.com');
        $user->setPassword('stored_hash');

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('find')->with(User::class, $user->getId())->willReturn($user);
        $em->expects(self::once())->method('flush');

        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        $hasher->method('isPasswordValid')->willReturnCallback(
            static function (User $u, string $plain): bool {
                if ($plain === 'correctOld') {
                    return true;
                }
                if ($plain === 'newPlain9') {
                    return false;
                }

                return false;
            }
        );
        $hasher->expects(self::once())->method('hashPassword')->with($user, 'newPlain9')->willReturn('new_hash');

        $service = new ChangeUserPasswordService($em, $hasher);
        $service->changePassword($user, 'correctOld', 'newPlain9', 'newPlain9');

        self::assertSame('new_hash', $user->getPassword());
    }

    public function testEmptyCurrentPasswordThrows(): void
    {
        $this->expectException(BusinessRuleException::class);
        $this->expectExceptionMessage('changePasswordCurrentRequired');

        $service = new ChangeUserPasswordService(
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(UserPasswordHasherInterface::class),
        );
        $service->changePassword(new User(), '', 'newpass9', 'newpass9');
    }

    public function testEmptyNewPasswordThrows(): void
    {
        $this->expectException(BusinessRuleException::class);
        $this->expectExceptionMessage('changePasswordNewRequired');

        $service = new ChangeUserPasswordService(
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(UserPasswordHasherInterface::class),
        );
        $service->changePassword(new User(), 'old', '', '');
    }

    public function testNewPasswordTooShortThrows(): void
    {
        $this->expectException(BusinessRuleException::class);
        $this->expectExceptionMessage('changePasswordNewTooShort');

        $service = new ChangeUserPasswordService(
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(UserPasswordHasherInterface::class),
        );
        $service->changePassword(new User(), 'old', '12345', '12345');
    }

    public function testMismatchRepeatThrows(): void
    {
        $this->expectException(BusinessRuleException::class);
        $this->expectExceptionMessage('changePasswordNewMismatch');

        $service = new ChangeUserPasswordService(
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(UserPasswordHasherInterface::class),
        );
        $service->changePassword(new User(), 'old', 'newpass9', 'other9');
    }

    public function testWhenUserMissingFromDatabaseThrows(): void
    {
        $user = new User();
        $user->setEmail('x@y.z');

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('find')->with(User::class, $user->getId())->willReturn(null);

        $this->expectException(BusinessRuleException::class);
        $this->expectExceptionMessage('changePasswordSessionInvalid');

        $service = new ChangeUserPasswordService($em, $this->createMock(UserPasswordHasherInterface::class));
        $service->changePassword($user, 'a', 'newpass9', 'newpass9');
    }

    public function testWrongCurrentPasswordThrows(): void
    {
        $user = new User();
        $user->setEmail('u@u.u');

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('find')->willReturn($user);

        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        $hasher->method('isPasswordValid')->willReturn(false);

        $this->expectException(BusinessRuleException::class);
        $this->expectExceptionMessage('changePasswordCurrentWrong');

        $service = new ChangeUserPasswordService($em, $hasher);
        $service->changePassword($user, 'wrong', 'newpass9', 'newpass9');
    }

    public function testSameAsCurrentPasswordThrows(): void
    {
        $user = new User();
        $user->setEmail('s@s.s');

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('find')->willReturn($user);

        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        $hasher->method('isPasswordValid')->willReturnCallback(
            static function (User $u, string $plain): bool {
                return $plain === 'sameBoth';
            }
        );

        $this->expectException(BusinessRuleException::class);
        $this->expectExceptionMessage('changePasswordSameAsCurrent');

        $service = new ChangeUserPasswordService($em, $hasher);
        $service->changePassword($user, 'sameBoth', 'sameBoth', 'sameBoth');
    }
}
