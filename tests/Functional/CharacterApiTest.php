<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Character\Domain\Model\Character;
use App\Character\Domain\Model\CharacterId;
use App\Character\Domain\Repository\CharacterRepository;
use App\Tests\Factory\UserFactory;
use PHPUnit\Framework\Attributes\DataProvider;

final class CharacterApiTest extends FunctionalTestCase
{
    private const APPEARANCE = ['face' => 3, 'hair' => 1, 'beard' => 7, 'tattoo' => 0, 'skin' => 4];

    public function testCreateRequiresToken(): void
    {
        $data = self::data($this->postJson('/api/characters', self::APPEARANCE));

        self::assertResponseStatusCodeSame(401);
        self::assertSame('JWT Token not found', $data['message'] ?? null);
    }

    public function testReadRequiresToken(): void
    {
        $this->getJson('/api/me/character');

        self::assertResponseStatusCodeSame(401);
    }

    public function testCreatesCharacterWithStartingValues(): void
    {
        $data = self::data($this->postJson('/api/characters', self::APPEARANCE, $this->tokenFor('gracz@example.com', 'Cebulak')));

        self::assertResponseStatusCodeSame(201);
        self::assertIsString($data['id'] ?? null);
        self::assertSame('Cebulak', $data['nick']);
        self::assertSame(1, $data['level']);
        self::assertSame(15, $data['maxHp']);
        self::assertSame(['current' => 0, 'required' => 100], $data['experience']);
        self::assertSame(['strength' => 1, 'endurance' => 1, 'technique' => 1, 'charisma' => 1], $data['stats']);
        self::assertSame(['current' => 100, 'max' => 100, 'nextPointInSeconds' => null], $data['stamina']);
        self::assertSame(['onions' => 100, 'goldTeeth' => 10, 'reach' => 0, 'medals' => 0], $data['wallet']);
        self::assertSame(['face' => 3, 'hair' => 1, 'beard' => 7, 'skin' => 4, 'tattoo' => 0], $data['appearance']);
    }

    public function testSecondCharacterForSameUserIsConflict(): void
    {
        $token = $this->tokenFor('gracz@example.com');
        $this->postJson('/api/characters', self::APPEARANCE, $token);
        self::assertResponseStatusCodeSame(201);

        $data = self::data($this->postJson('/api/characters', self::APPEARANCE, $token));

        self::assertResponseStatusCodeSame(409);
        self::assertSame('Character already exists.', $data['detail'] ?? null);
    }

    /**
     * @return iterable<string, array{array<string, int>, string, string}>
     */
    public static function invalidAppearances(): iterable
    {
        yield 'skin 5' => [['skin' => 5], 'skin', 'This value should be between 0 and 4.'];
        yield 'face 8' => [['face' => 8], 'face', 'This value should be between 0 and 7.'];
        yield 'hair negative' => [['hair' => -1], 'hair', 'This value should be between 0 and 7.'];
        yield 'beard 8' => [['beard' => 8], 'beard', 'This value should be between 0 and 7.'];
        yield 'tattoo 99' => [['tattoo' => 99], 'tattoo', 'This value should be between 0 and 7.'];
    }

    /**
     * @param array<string, int> $override
     */
    #[DataProvider('invalidAppearances')]
    public function testRejectsAppearanceOutOfRange(array $override, string $field, string $message): void
    {
        $data = self::data($this->postJson('/api/characters', [...self::APPEARANCE, ...$override], $this->tokenFor('gracz@example.com')));

        self::assertResponseStatusCodeSame(422);
        self::assertSame([$field => $message], self::violations($data));
    }

    public function testRejectsMissingAppearanceFields(): void
    {
        $data = self::data($this->postJson('/api/characters', ['face' => 1], $this->tokenFor('gracz@example.com')));

        self::assertResponseStatusCodeSame(422);
        self::assertSame(['hair', 'beard', 'skin', 'tattoo'], array_keys(self::violations($data)));
    }

    public function testInvalidRequestDoesNotCreateCharacter(): void
    {
        $token = $this->tokenFor('gracz@example.com');
        $this->postJson('/api/characters', [...self::APPEARANCE, 'skin' => 9], $token);

        $this->getJson('/api/me/character', $token);

        self::assertResponseStatusCodeSame(404);
    }

    public function testReadsOwnCharacter(): void
    {
        $token = $this->tokenFor('gracz@example.com', 'Kapitan');
        $created = self::data($this->postJson('/api/characters', self::APPEARANCE, $token));

        $data = self::data($this->getJson('/api/me/character', $token));

        self::assertResponseStatusCodeSame(200);
        self::assertSame('Kapitan', $data['nick']);
        self::assertSame(['current' => 0, 'required' => 100], $data['experience']);
        self::assertSame($created, $data);
    }

    public function testReadsExperienceTowardsNextLevel(): void
    {
        $token = $this->tokenFor('gracz@example.com');
        $created = self::data($this->postJson('/api/characters', self::APPEARANCE, $token));
        self::assertIsString($created['id'] ?? null);
        $this->grantExperience($created['id'], 350);

        $data = self::data($this->getJson('/api/me/character', $token));

        self::assertResponseStatusCodeSame(200);
        self::assertSame(3, $data['level']);
        self::assertSame(['current' => 50, 'required' => 300], $data['experience']);
        self::assertSame(25, $data['maxHp']);
    }

    public function testReadsStaminaRegeneratingAfterSpending(): void
    {
        $token = $this->tokenFor('gracz@example.com');
        $created = self::data($this->postJson('/api/characters', self::APPEARANCE, $token));
        self::assertIsString($created['id'] ?? null);
        $this->changeCharacter($created['id'], static fn (Character $character) => $character->spendStamina(10, new \DateTimeImmutable()));

        $data = self::data($this->getJson('/api/me/character', $token));

        self::assertResponseStatusCodeSame(200);
        self::assertIsArray($data['stamina'] ?? null);
        self::assertSame(90, $data['stamina']['current']);
        self::assertSame(100, $data['stamina']['max']);
        self::assertIsInt($data['stamina']['nextPointInSeconds']);
        self::assertGreaterThan(170, $data['stamina']['nextPointInSeconds']);
        self::assertLessThanOrEqual(180, $data['stamina']['nextPointInSeconds']);
    }

    public function testReadWithoutCharacterIsNotFound(): void
    {
        $data = self::data($this->getJson('/api/me/character', $this->tokenFor('gracz@example.com')));

        self::assertResponseStatusCodeSame(404);
        self::assertSame('Character not found.', $data['detail'] ?? null);
    }

    public function testDoesNotReturnCharacterOfAnotherUser(): void
    {
        $this->postJson('/api/characters', self::APPEARANCE, $this->tokenFor('pierwszy@example.com'));
        self::assertResponseStatusCodeSame(201);

        $this->getJson('/api/me/character', $this->tokenFor('drugi@example.com'));

        self::assertResponseStatusCodeSame(404);
    }

    public function testEndpointsAreDocumented(): void
    {
        $docs = self::data($this->client->request('GET', '/api/docs.jsonopenapi', ['headers' => ['Accept' => 'application/vnd.openapi+json']]));

        self::assertResponseIsSuccessful();
        self::assertIsArray($docs['paths'] ?? null);
        self::assertArrayHasKey('post', $docs['paths']['/api/characters'] ?? []);
        self::assertArrayHasKey('get', $docs['paths']['/api/me/character'] ?? []);
        self::assertSame(['201', '401', '409', '422'], self::documentedStatuses($docs['paths']['/api/characters']['post'], ['201', '401', '409', '422']));
        self::assertSame(['200', '401', '404'], self::documentedStatuses($docs['paths']['/api/me/character']['get'], ['200', '401', '404']));
    }

    /**
     * @param array<string, mixed> $operation
     * @param list<string>         $expected
     *
     * @return list<string>
     */
    private static function documentedStatuses(array $operation, array $expected): array
    {
        $responses = $operation['responses'] ?? [];
        self::assertIsArray($responses);

        return array_values(array_filter($expected, static fn (string $status): bool => \array_key_exists($status, $responses)));
    }

    private function grantExperience(string $characterId, int $points): void
    {
        $this->changeCharacter($characterId, static fn (Character $character) => $character->gainExperience($points));
    }

    /**
     * @param callable(Character): void $change
     */
    private function changeCharacter(string $characterId, callable $change): void
    {
        $characters = self::getContainer()->get(CharacterRepository::class);
        $character = $characters->ofId(CharacterId::fromString($characterId));
        self::assertNotNull($character);

        $change($character);
        $characters->save($character);
    }

    private function tokenFor(string $email, ?string $nick = null): string
    {
        UserFactory::createOne(null === $nick ? ['email' => $email] : ['email' => $email, 'nick' => $nick]);

        return $this->login($email, UserFactory::DEFAULT_PASSWORD)['token'];
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, string>
     */
    private static function violations(array $data): array
    {
        $violations = $data['violations'] ?? null;
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
