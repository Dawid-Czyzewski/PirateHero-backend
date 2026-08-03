<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Tests\Functional\ApiWebTestCase;

final class PasswordResetTest extends ApiWebTestCase
{
    public function testRequestResetForUnknownEmailStillReturnsSuccess(): void
    {
        $client = static::ensureTestClient();
        $client->request(
            'POST',
            '/api/password-reset/request',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => 'nobody@example.com'], \JSON_THROW_ON_ERROR),
        );

        self::assertSame(200, $client->getResponse()->getStatusCode());
        $body = json_decode($client->getResponse()->getContent() ?: '', true, 512, \JSON_THROW_ON_ERROR);
        self::assertTrue($body['data']['sent'] ?? false);
    }

    public function testCompleteResetWithValidToken(): void
    {
        $plain = 'Test_SecurePass_9';
        $user = $this->makePersistedActivatedUser($plain);
        $token = $user->issuePasswordResetToken(new \DateTimeImmutable('+1 hour'));
        $this->entityManager()->persist($user);
        $this->entityManager()->flush();

        $client = static::ensureTestClient();
        $newPlain = 'New_ResetPass_1';
        $client->request(
            'POST',
            '/api/password-reset/complete',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'token' => $token,
                'newPassword' => $newPlain,
                'newPasswordRepeat' => $newPlain,
            ], \JSON_THROW_ON_ERROR),
        );

        self::assertSame(200, $client->getResponse()->getStatusCode());

        $this->entityManager()->clear();
        $reloaded = $this->entityManager()->getRepository(\App\Entity\User::class)->find($user->getId());
        self::assertNotNull($reloaded);
        self::assertNull($reloaded->getPasswordResetToken());
        $hasher = static::getContainer()->get(\Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface::class);
        self::assertTrue($hasher->isPasswordValid($reloaded, $newPlain));
    }
}
