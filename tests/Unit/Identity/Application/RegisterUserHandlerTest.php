<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Application;

use App\Identity\Application\Command\RegisterUser\RegisterUserCommand;
use App\Identity\Application\Command\RegisterUser\RegisterUserHandler;
use App\Identity\Domain\Exception\InvalidUserData;
use App\Identity\Domain\Exception\UserAlreadyExists;
use App\Identity\Domain\Model\User;
use App\Tests\Double\Identity\FakePasswordHasher;
use App\Tests\Double\Identity\InMemoryUserRepository;
use App\Tests\Double\Identity\UserMother;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;

final class RegisterUserHandlerTest extends TestCase
{
    private InMemoryUserRepository $users;
    private RegisterUserHandler $handler;

    protected function setUp(): void
    {
        $this->users = new InMemoryUserRepository();
        $this->handler = new RegisterUserHandler($this->users, new FakePasswordHasher(), new MockClock(new \DateTimeImmutable('2026-11-03 20:00:00')));
    }

    public function testRegistersUser(): void
    {
        $id = ($this->handler)(new RegisterUserCommand(' Gracz@Example.com ', ' Cebulak ', 'tajnehaslo'));

        $user = $this->users->ofId($id);
        self::assertInstanceOf(User::class, $user);
        self::assertSame('gracz@example.com', $user->email()->toString());
        self::assertSame('Cebulak', $user->nick()->toString());
        self::assertSame('hashed:tajnehaslo', $user->password()->toString());
        self::assertEquals(new \DateTimeImmutable('2026-11-03 20:00:00'), $user->createdAt());
    }

    public function testRejectsTakenEmail(): void
    {
        $this->users->save(UserMother::create('gracz@example.com', 'Inny'));

        $this->expectConflicts(['email'], new RegisterUserCommand('GRACZ@example.com', 'Cebulak', 'tajnehaslo'));
    }

    public function testRejectsTakenNick(): void
    {
        $this->users->save(UserMother::create('inny@example.com', 'Cebulak'));

        $this->expectConflicts(['nick'], new RegisterUserCommand('gracz@example.com', 'Cebulak', 'tajnehaslo'));
    }

    public function testReportsBothConflictsAtOnce(): void
    {
        $this->users->save(UserMother::create('gracz@example.com', 'Cebulak'));

        $this->expectConflicts(['email', 'nick'], new RegisterUserCommand('gracz@example.com', 'Cebulak', 'tajnehaslo'));
    }

    public function testRejectsInvalidDataWithoutSaving(): void
    {
        try {
            ($this->handler)(new RegisterUserCommand('gracz@example.com', 'Cebulak', 'krotkie'));
            self::fail('Expected InvalidUserData');
        } catch (InvalidUserData $e) {
            self::assertSame('password', $e->field);
        }

        self::assertSame(0, $this->users->count());
    }

    /**
     * @param list<'email'|'nick'> $expected
     */
    private function expectConflicts(array $expected, RegisterUserCommand $command): void
    {
        try {
            ($this->handler)($command);
            self::fail('Expected UserAlreadyExists');
        } catch (UserAlreadyExists $e) {
            self::assertSame($expected, $e->conflicts);
        }

        self::assertSame(1, $this->users->count());
    }
}
