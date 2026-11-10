<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Identity\Domain\Model\Email;
use App\Identity\Domain\Model\User;
use App\Identity\Domain\Repository\UserRepository;
use App\Tests\Factory\UserFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class RegisterTest extends FunctionalTestCase
{
    public function testRegistersUserWithoutExposingPassword(): void
    {
        $data = self::data($this->postJson('/api/auth/register', [
            'email' => 'Gracz@Example.com',
            'nick' => 'Cebulak',
            'password' => 'tajnehaslo',
        ]));

        self::assertResponseStatusCodeSame(201);
        self::assertSame('gracz@example.com', $data['email']);
        self::assertSame('Cebulak', $data['nick']);
        self::assertSame(['ROLE_USER'], $data['roles']);
        self::assertArrayHasKey('id', $data);
        self::assertArrayHasKey('createdAt', $data);
        self::assertArrayNotHasKey('password', $data);

        $user = static::getContainer()->get(UserRepository::class)->ofEmail(new Email('gracz@example.com'));
        self::assertInstanceOf(User::class, $user);
        self::assertSame($data['id'], $user->id()->toString());
        self::assertTrue(password_verify('tajnehaslo', $user->password()->toString()));
    }

    /**
     * @return iterable<string, array{array<string, string>, string}>
     */
    public static function invalidPayloads(): iterable
    {
        yield 'invalid email' => [['email' => 'not-an-email', 'nick' => 'Cebulak', 'password' => 'tajnehaslo'], 'email'];
        yield 'nick too short' => [['email' => 'a@example.com', 'nick' => 'ab', 'password' => 'tajnehaslo'], 'nick'];
        yield 'nick too long' => [['email' => 'a@example.com', 'nick' => str_repeat('a', 17), 'password' => 'tajnehaslo'], 'nick'];
        yield 'password too short' => [['email' => 'a@example.com', 'nick' => 'Cebulak', 'password' => 'krotkie'], 'password'];
    }

    /**
     * @param array<string, string> $payload
     */
    #[DataProvider('invalidPayloads')]
    public function testRejectsInvalidPayload(array $payload, string $field): void
    {
        $response = $this->postJson('/api/auth/register', $payload);

        self::assertResponseStatusCodeSame(422);
        self::assertContains($field, self::violatedFields($response));
    }

    public function testRejectsTakenEmailAndNick(): void
    {
        UserFactory::createOne(['email' => 'zajety@example.com', 'nick' => 'Zajety']);

        $response = $this->postJson('/api/auth/register', ['email' => 'zajety@example.com', 'nick' => 'Zajety', 'password' => 'tajnehaslo']);

        self::assertResponseStatusCodeSame(422);
        $fields = self::violatedFields($response);
        self::assertContains('email', $fields);
        self::assertContains('nick', $fields);
    }

    public function testRegistrationIsRateLimited(): void
    {
        for ($i = 1; $i <= 5; ++$i) {
            $this->postJson('/api/auth/register', ['email' => "gracz{$i}@example.com", 'nick' => "Gracz{$i}", 'password' => 'tajnehaslo']);
            self::assertResponseStatusCodeSame(201);
        }

        $data = self::data($this->postJson('/api/auth/register', ['email' => 'gracz6@example.com', 'nick' => 'Gracz6', 'password' => 'tajnehaslo']));
        self::assertResponseStatusCodeSame(429);
        self::assertSame('Too many registration attempts. Please try again later.', $data['detail'] ?? null);
    }

    public function testValidationErrorsAreInEnglish(): void
    {
        $response = $this->postJson('/api/auth/register', ['email' => 'not-an-email', 'nick' => 'ab', 'password' => 'krotkie']);

        self::assertResponseStatusCodeSame(422);
        self::assertSame([
            'email' => 'This value is not a valid email address.',
            'nick' => 'This value is too short. It should have 3 characters or more.',
            'password' => 'This value is too short. It should have 8 characters or more.',
        ], self::violationMessages($response));
    }

    public function testConflictErrorsAreInEnglish(): void
    {
        UserFactory::createOne(['email' => 'zajety@example.com', 'nick' => 'Zajety']);

        $response = $this->postJson('/api/auth/register', ['email' => 'zajety@example.com', 'nick' => 'Zajety', 'password' => 'tajnehaslo']);

        self::assertSame([
            'email' => 'This email is already taken.',
            'nick' => 'This nick is already taken.',
        ], self::violationMessages($response));
    }

    /**
     * @return list<string>
     */
    private static function violatedFields(ResponseInterface $response): array
    {
        return array_keys(self::violationMessages($response));
    }

    /**
     * @return array<string, string>
     */
    private static function violationMessages(ResponseInterface $response): array
    {
        $violations = self::data($response)['violations'] ?? null;
        self::assertIsArray($violations);

        $messages = [];
        foreach ($violations as $violation) {
            self::assertIsArray($violation);
            self::assertIsString($violation['propertyPath'] ?? null);
            self::assertIsString($violation['message'] ?? null);
            $messages[$violation['propertyPath']] = $violation['message'];
        }

        return $messages;
    }
}
