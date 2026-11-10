<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Presentation;

use ApiPlatform\Metadata\Post;
use ApiPlatform\Validator\Exception\ValidationException;
use App\Identity\Application\Command\RegisterUser\RegisterUserHandler;
use App\Identity\Application\Query\GetUser\GetUserHandler;
use App\Identity\Presentation\Api\RegisterUserInput;
use App\Identity\Presentation\Api\State\RegisterUserProcessor;
use App\Tests\Double\Identity\FakePasswordHasher;
use App\Tests\Double\Identity\InMemoryUserRepository;
use App\Tests\Double\Identity\UserMother;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\InMemoryStorage;

final class RegisterUserProcessorTest extends TestCase
{
    private InMemoryUserRepository $users;
    private RateLimiterFactory $limiter;

    protected function setUp(): void
    {
        $this->users = new InMemoryUserRepository();
        $this->limiter = $this->limiterFactory(5);
    }

    public function testReturnsRegisteredUserResource(): void
    {
        $resource = $this->processor()->process($this->input('gracz@example.com', 'Cebulak'), new Post());

        self::assertSame('gracz@example.com', $resource->email);
        self::assertSame('Cebulak', $resource->nick);
        self::assertSame(['ROLE_USER'], $resource->roles);
        self::assertSame(1, $this->users->count());
    }

    public function testMapsConflictsToViolationsOnEachField(): void
    {
        $this->users->save(UserMother::create('gracz@example.com', 'Cebulak'));

        try {
            $this->processor()->process($this->input('gracz@example.com', 'Cebulak'), new Post());
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $fields = [];
            foreach ($e->getConstraintViolationList() as $violation) {
                $fields[] = $violation->getPropertyPath();
            }
            self::assertSame(['email', 'nick'], $fields);
        }
    }

    public function testMapsDomainRuleViolationTo422WithEnglishMessage(): void
    {
        try {
            $this->processor()->process($this->input('gracz@example.com', 'ab'), new Post());
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $violation = $e->getConstraintViolationList()->get(0);
            self::assertSame('nick', $violation->getPropertyPath());
            self::assertSame('Nick must be between 3 and 16 characters long.', $violation->getMessage());
        }
    }

    public function testThrows429AfterLimitIsExhausted(): void
    {
        $this->limiter = $this->limiterFactory(2);
        $processor = $this->processor();

        $processor->process($this->input('a@example.com', 'Gracz1'), new Post());
        $processor->process($this->input('b@example.com', 'Gracz2'), new Post());

        $this->expectException(TooManyRequestsHttpException::class);
        $processor->process($this->input('c@example.com', 'Gracz3'), new Post());
    }

    public function testLimitIsCountedPerIp(): void
    {
        $this->limiter = $this->limiterFactory(1);

        $this->processor('10.0.0.1')->process($this->input('a@example.com', 'Gracz1'), new Post());
        $resource = $this->processor('10.0.0.2')->process($this->input('b@example.com', 'Gracz2'), new Post());

        self::assertSame('Gracz2', $resource->nick);
    }

    private function processor(string $ip = '127.0.0.1'): RegisterUserProcessor
    {
        $requestStack = new RequestStack();
        $requestStack->push(Request::create('/api/auth/register', 'POST', server: ['REMOTE_ADDR' => $ip]));

        return new RegisterUserProcessor(
            new RegisterUserHandler($this->users, new FakePasswordHasher(), new MockClock()),
            new GetUserHandler($this->users),
            $requestStack,
            $this->limiter,
        );
    }

    private function limiterFactory(int $limit): RateLimiterFactory
    {
        return new RateLimiterFactory(
            ['id' => 'register', 'policy' => 'sliding_window', 'limit' => $limit, 'interval' => '15 minutes'],
            new InMemoryStorage(),
        );
    }

    private function input(string $email, string $nick): RegisterUserInput
    {
        $input = new RegisterUserInput();
        $input->email = $email;
        $input->nick = $nick;
        $input->password = 'tajnehaslo';

        return $input;
    }
}
