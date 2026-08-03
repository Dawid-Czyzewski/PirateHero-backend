<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\User;
use App\Tests\Functional\ApiWebTestCase;

final class RegistrationFlowTest extends ApiWebTestCase
{
    public function testRegisterSuccessReturnsCreatedEnvelope(): void
    {
        $client = static::ensureTestClient();
        $payload = [
            'email' => sprintf('new_%s@test.local', bin2hex(random_bytes(4))),
            'username' => sprintf('user_%s', bin2hex(random_bytes(3))),
            'password' => 'Test_SecurePass_9',
            'passwordRepeat' => 'Test_SecurePass_9',
            'rulesAccepted' => true,
            'avatarName' => 'captain',
        ];

        $client->request('POST', '/api/register', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($payload, \JSON_THROW_ON_ERROR));
        self::assertSame(201, $client->getResponse()->getStatusCode(), $client->getResponse()->getContent());
        $envelope = $this->assertJsonEnvelopeSuccess($client->getResponse());
        self::assertTrue($envelope['data']['registered']);
        self::assertSame('userRegisteredSuccessfully', $envelope['meta']['message']);
    }

    public function testRegisterDuplicateEmailReturnsBusinessError(): void
    {
        $existing = $this->makePersistedActivatedUser();
        $client = static::ensureTestClient();
        $payload = [
            'email' => $existing->getEmail(),
            'username' => sprintf('unique_%s', bin2hex(random_bytes(3))),
            'password' => 'Test_SecurePass_9',
            'passwordRepeat' => 'Test_SecurePass_9',
            'rulesAccepted' => true,
            'avatarName' => 'captain',
        ];

        $client->request('POST', '/api/register', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($payload, \JSON_THROW_ON_ERROR));
        self::assertGreaterThanOrEqual(400, $client->getResponse()->getStatusCode());
        self::assertLessThan(500, $client->getResponse()->getStatusCode());
    }

    public function testRegisterDuplicateUsernameReturnsBusinessError(): void
    {
        $existing = $this->makePersistedActivatedUser();
        $client = static::ensureTestClient();
        $payload = [
            'email' => sprintf('other_%s@test.local', bin2hex(random_bytes(4))),
            'username' => $existing->getUsername(),
            'password' => 'Test_SecurePass_9',
            'passwordRepeat' => 'Test_SecurePass_9',
            'rulesAccepted' => true,
            'avatarName' => 'captain',
        ];

        $client->request('POST', '/api/register', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($payload, \JSON_THROW_ON_ERROR));
        self::assertGreaterThanOrEqual(400, $client->getResponse()->getStatusCode());
        self::assertLessThan(500, $client->getResponse()->getStatusCode());
    }

    public function testRegisterActuallyPersistsUser(): void
    {
        $client = static::ensureTestClient();
        $email = sprintf('persist_%s@test.local', bin2hex(random_bytes(4)));
        $username = sprintf('persist_%s', bin2hex(random_bytes(3)));

        $client->request('POST', '/api/register', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'email' => $email,
            'username' => $username,
            'password' => 'Test_SecurePass_9',
            'passwordRepeat' => 'Test_SecurePass_9',
            'rulesAccepted' => true,
            'avatarName' => 'korsarz',
        ], \JSON_THROW_ON_ERROR));
        self::assertSame(201, $client->getResponse()->getStatusCode());

        $saved = $this->entityManager()->getRepository(User::class)->findOneBy(['email' => $email]);
        self::assertNotNull($saved);
        self::assertSame($username, $saved->getUsername());
        self::assertSame('avatar1', $saved->getAvatarName());
    }

    public function testRegisterWithoutAvatarNameReturnsClientError(): void
    {
        $client = static::ensureTestClient();
        $payload = [
            'email' => sprintf('missing_avatar_%s@test.local', bin2hex(random_bytes(4))),
            'username' => sprintf('user_%s', bin2hex(random_bytes(3))),
            'password' => 'Test_SecurePass_9',
            'passwordRepeat' => 'Test_SecurePass_9',
            'rulesAccepted' => true,
        ];

        $client->request('POST', '/api/register', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($payload, \JSON_THROW_ON_ERROR));
        self::assertGreaterThanOrEqual(400, $client->getResponse()->getStatusCode());
        self::assertLessThan(500, $client->getResponse()->getStatusCode());
    }
}
