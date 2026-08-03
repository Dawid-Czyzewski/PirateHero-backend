<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Tests\Functional\ApiWebTestCase;

final class AccountChangePasswordTest extends ApiWebTestCase
{
    public function testChangePasswordWithWrongCurrentReturnsProblemJson(): void
    {
        $plain = 'Test_SecurePass_9';
        $client = $this->createAuthenticatedClient(null, $plain);

        $client->request(
            'POST',
            '/api/account/change-password',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'currentPassword' => 'wrong-pass',
                'newPassword' => 'New_SecurePass_1',
                'newPasswordRepeat' => 'New_SecurePass_1',
            ], \JSON_THROW_ON_ERROR),
        );

        self::assertSame(400, $client->getResponse()->getStatusCode());
        $problem = $this->assertProblemJson($client->getResponse());
        self::assertSame('changePasswordCurrentWrong', $problem['detail']);
    }

    public function testChangePasswordSuccessThenLoginWithNewPassword(): void
    {
        $oldPlain = 'Test_SecurePass_9';
        $newPlain = 'New_SecurePass_AA';
        $user = $this->makePersistedActivatedUser($oldPlain);
        $email = (string) $user->getEmail();

        $client = $this->createAuthenticatedClient($user, $oldPlain);
        $client->request(
            'POST',
            '/api/account/change-password',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'currentPassword' => $oldPlain,
                'newPassword' => $newPlain,
                'newPasswordRepeat' => $newPlain,
            ], \JSON_THROW_ON_ERROR),
        );

        self::assertSame(200, $client->getResponse()->getStatusCode());
        $envelope = $this->assertJsonEnvelopeSuccess($client->getResponse());
        self::assertTrue($envelope['data']['changed'] ?? false);

        static::ensureKernelShutdown();
        $loginClient = static::ensureTestClient();
        $loginClient->request('POST', '/api/login', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'email' => $email,
            'password' => $oldPlain,
        ], \JSON_THROW_ON_ERROR));
        self::assertSame(401, $loginClient->getResponse()->getStatusCode());

        static::ensureKernelShutdown();
        $loginClient2 = static::ensureTestClient();
        $loginClient2->request('POST', '/api/login', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'email' => $email,
            'password' => $newPlain,
        ], \JSON_THROW_ON_ERROR));
        self::assertSame(200, $loginClient2->getResponse()->getStatusCode(), $loginClient2->getResponse()->getContent());
    }

    public function testMismatchRepeatReturnsBadRequest(): void
    {
        $plain = 'Test_SecurePass_9';
        $client = $this->createAuthenticatedClient(null, $plain);

        $client->request(
            'POST',
            '/api/account/change-password',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'currentPassword' => $plain,
                'newPassword' => 'New_SecurePass_1',
                'newPasswordRepeat' => 'New_SecurePass_2',
            ], \JSON_THROW_ON_ERROR),
        );

        self::assertSame(400, $client->getResponse()->getStatusCode());
        $problem = $this->assertProblemJson($client->getResponse());
        self::assertSame('changePasswordNewMismatch', $problem['detail']);
    }

    public function testInvalidPayloadReturnsBadRequest(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'POST',
            '/api/account/change-password',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['currentPassword' => 'x'], \JSON_THROW_ON_ERROR),
        );

        self::assertSame(400, $client->getResponse()->getStatusCode());
        $problem = $this->assertProblemJson($client->getResponse());
        self::assertSame('changePasswordInvalidPayload', $problem['detail']);
    }
}
