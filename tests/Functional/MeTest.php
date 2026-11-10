<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Tests\Factory\UserFactory;

final class MeTest extends FunctionalTestCase
{
    public function testRequiresToken(): void
    {
        $data = self::data($this->getJson('/api/me'));

        self::assertResponseStatusCodeSame(401);
        self::assertSame('JWT Token not found', $data['message'] ?? null);
    }

    public function testRejectsInvalidToken(): void
    {
        $data = self::data($this->getJson('/api/me', 'not.a.jwt'));

        self::assertResponseStatusCodeSame(401);
        self::assertSame('Invalid JWT Token', $data['message'] ?? null);
    }

    public function testReturnsCurrentUserWithValidToken(): void
    {
        UserFactory::createOne(['email' => 'gracz@example.com', 'nick' => 'Cebulak']);
        $tokens = $this->login('gracz@example.com', UserFactory::DEFAULT_PASSWORD);

        $data = self::data($this->getJson('/api/me', $tokens['token']));

        self::assertResponseIsSuccessful();
        self::assertSame('gracz@example.com', $data['email']);
        self::assertSame('Cebulak', $data['nick']);
        self::assertArrayNotHasKey('password', $data);
    }
}
