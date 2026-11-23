<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use PHPUnit\Framework\Attributes\DataProvider;

final class CombatApiTest extends FunctionalTestCase
{
    private const APPEARANCE = ['face' => 3, 'hair' => 1, 'beard' => 7, 'tattoo' => 0, 'skin' => 4];

    public function testNpcListRequiresToken(): void
    {
        $this->getJson('/api/npcs');

        self::assertResponseStatusCodeSame(401);
    }

    public function testListsEightNpcsOrderedByLevel(): void
    {
        $data = array_values(self::data($this->getJson('/api/npcs', $this->tokenFor('gracz@example.com'))));

        self::assertResponseIsSuccessful();
        self::assertCount(8, $data);
        self::assertSame(range(1, 8), array_column($data, 'level'));
        self::assertSame(
            [
                'id' => 'kosa',
                'nick' => 'Kosa',
                'level' => 1,
                'maxHp' => 15,
                'stats' => ['strength' => 1, 'endurance' => 1, 'technique' => 1, 'charisma' => 1],
                'look' => ['face' => 0, 'hair' => 2, 'beard' => 0, 'skin' => 1, 'tattoo' => 0],
            ],
            $data[0],
        );
    }

    public function testFightRequiresToken(): void
    {
        $this->postJson('/api/me/fights', ['npcId' => 'kosa']);

        self::assertResponseStatusCodeSame(401);
    }

    public function testFightRequiresCharacter(): void
    {
        $data = self::data($this->postJson('/api/me/fights', ['npcId' => 'kosa'], $this->tokenFor('gracz@example.com')));

        self::assertResponseStatusCodeSame(404);
        self::assertSame('Character not found.', $data['detail'] ?? null);
    }

    public function testUnknownOpponentIsNotFound(): void
    {
        $data = self::data($this->postJson('/api/me/fights', ['npcId' => 'ktos-nieznany'], $this->playerWithCharacter()));

        self::assertResponseStatusCodeSame(404);
        self::assertSame('Opponent not found.', $data['detail'] ?? null);
    }

    /**
     * @return iterable<string, array{array<string, mixed>}>
     */
    public static function invalidBodies(): iterable
    {
        yield 'missing' => [[]];
        yield 'blank' => [['npcId' => '']];
        yield 'not a slug' => [['npcId' => 'Kosa!']];
    }

    /**
     * @param array<string, mixed> $body
     */
    #[DataProvider('invalidBodies')]
    public function testRejectsInvalidOpponentId(array $body): void
    {
        $this->postJson('/api/me/fights', $body, $this->playerWithCharacter());

        self::assertResponseStatusCodeSame(422);
    }

    public function testFightReturnsTheBoutAndSavesTheReward(): void
    {
        $token = $this->playerWithCharacter();

        $fight = self::data($this->postJson('/api/me/fights', ['npcId' => 'kosa'], $token));

        self::assertResponseIsSuccessful();
        self::assertSame('Cebulak', $fight['player']['name']);
        self::assertSame('kosa', $fight['npc']['id']);
        self::assertContains($fight['winner'], ['player', 'npc']);
        self::assertIsBool($fight['knockout']);
        self::assertNotEmpty($fight['rounds']);
        self::assertSame(['number', 'attacker', 'damage', 'playerHp', 'npcHp'], array_keys($fight['rounds'][0]));
        self::assertSame(['before' => 1, 'after' => 1], $fight['level']);
        self::assertSame(0, $fight['staminaCost']);

        $character = self::data($this->getJson('/api/me/character', $token));
        self::assertSame($fight['reward']['experience'], $character['experience']['current']);
        self::assertSame(100 + $fight['reward']['onions'], $character['wallet']['onions']);
        self::assertSame(100, $character['stamina']['current']);
    }

    public function testFightIsDocumented(): void
    {
        $paths = self::data($this->client->request('GET', '/api/docs', ['headers' => ['Accept' => 'application/vnd.openapi+json']]))['paths'];

        self::assertArrayHasKey('/api/npcs', $paths);
        self::assertArrayHasKey('/api/me/fights', $paths);
    }

    private function playerWithCharacter(): string
    {
        $token = $this->tokenFor('gracz@example.com', 'Cebulak');
        $this->postJson('/api/characters', self::APPEARANCE, $token);
        self::assertResponseStatusCodeSame(201);

        return $token;
    }
}
