<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Tests\Functional\ApiWebTestCase;

final class PublicAuthEndpointsTest extends ApiWebTestCase
{
    public function testLoginWithWrongPasswordReturnsUnauthorized(): void
    {
        $plainPassword = 'Test_SecurePass_9';
        $user = $this->makePersistedActivatedUser($plainPassword);

        $client = static::ensureTestClient();
        $client->request('POST', '/api/login', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'email' => $user->getEmail(),
            'password' => 'wrong-password',
        ], \JSON_THROW_ON_ERROR));

        self::assertSame(401, $client->getResponse()->getStatusCode());
    }

    public function testLoginUnactivatedAccountReturns403ProblemJson(): void
    {
        $plainPassword = 'Test_SecurePass_9';
        $user = $this->makePersistedUnactivatedUser($plainPassword);

        $client = static::ensureTestClient();
        $client->request('POST', '/api/login', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'email' => $user->getEmail(),
            'password' => $plainPassword,
        ], \JSON_THROW_ON_ERROR));

        self::assertSame(403, $client->getResponse()->getStatusCode());
        $problem = $this->assertProblemJson($client->getResponse());
        self::assertSame('accountNotActivated', $problem['detail']);
    }

    public function testLoginActivatedAccountReturnsEnvelopeWithTokens(): void
    {
        $plainPassword = 'Test_SecurePass_9';
        $user = $this->makePersistedActivatedUser($plainPassword);

        $client = static::ensureTestClient();
        $client->request('POST', '/api/login', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'email' => $user->getEmail(),
            'password' => $plainPassword,
        ], \JSON_THROW_ON_ERROR));

        self::assertSame(200, $client->getResponse()->getStatusCode());
        $payload = $this->assertJsonEnvelopeSuccess($client->getResponse());
        self::assertArrayHasKey('token', $payload['data']);
        self::assertArrayHasKey('refresh_token', $payload['data']);
        self::assertArrayHasKey('user', $payload['data']);
    }

    public function testTokenRefreshReturnsProblemWhenTokenMissing(): void
    {
        $client = static::ensureTestClient();
        $client->request('POST', '/api/token/refresh', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'refreshToken' => 'invalid',
        ], \JSON_THROW_ON_ERROR));
        self::assertSame(401, $client->getResponse()->getStatusCode());
    }

    public function testTokenRefreshWithValidRefreshTokenReturnsNewJwt(): void
    {
        $plainPassword = 'Test_SecurePass_9';
        $user = $this->makePersistedActivatedUser($plainPassword);

        $client = static::ensureTestClient();
        $client->request('POST', '/api/login', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'email' => $user->getEmail(),
            'password' => $plainPassword,
        ], \JSON_THROW_ON_ERROR));
        self::assertSame(200, $client->getResponse()->getStatusCode(), $client->getResponse()->getContent());
        $loginEnvelope = $this->assertJsonEnvelopeSuccess($client->getResponse());
        self::assertArrayHasKey('refresh_token', $loginEnvelope['data']);

        $client->request('POST', '/api/token/refresh', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'refreshToken' => $loginEnvelope['data']['refresh_token'],
        ], \JSON_THROW_ON_ERROR));
        self::assertSame(200, $client->getResponse()->getStatusCode(), $client->getResponse()->getContent());
        $refreshEnvelope = $this->assertJsonEnvelopeSuccess($client->getResponse());
        self::assertArrayHasKey('token', $refreshEnvelope['data']);
    }

    public function testRegisterWithInvalidPayloadReturnsClientError(): void
    {
        $client = static::ensureTestClient();
        $client->request('POST', '/api/register', [], [], ['CONTENT_TYPE' => 'application/json'], '{}');
        self::assertGreaterThanOrEqual(400, $client->getResponse()->getStatusCode());
        self::assertLessThan(500, $client->getResponse()->getStatusCode());
    }

    public function testActivateAccountWithInvalidTokenReturnsClientError(): void
    {
        $client = static::ensureTestClient();
        $client->request('GET', '/api/activate-account/not-existing-token');
        self::assertGreaterThanOrEqual(400, $client->getResponse()->getStatusCode());
        self::assertLessThan(500, $client->getResponse()->getStatusCode());
    }
}
