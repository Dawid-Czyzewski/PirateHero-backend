<?php

declare(strict_types=1);

namespace App\Tests\Unit\Http;

use App\Http\ApiEnvelope;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

final class ApiEnvelopeTest extends TestCase
{
    public function testSuccessWrapsDataAndMetaMessage(): void
    {
        $payload = ApiEnvelope::success(['gold' => 10], 'itemPurchased');
        self::assertSame(['gold' => 10], $payload['data']);
        self::assertSame(['message' => 'itemPurchased'], $payload['meta']);
    }

    public function testJsonResponseUses200ByDefault(): void
    {
        $response = ApiEnvelope::jsonResponse(['ok' => true], 'done');
        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        $decoded = json_decode($response->getContent(), true);
        self::assertIsArray($decoded);
        self::assertTrue($decoded['data']['ok']);
        self::assertSame('done', $decoded['meta']['message']);
    }

    public function testJsonResponseAcceptsCreatedStatus(): void
    {
        $response = ApiEnvelope::jsonResponse(['registered' => true], 'userRegisteredSuccessfully', Response::HTTP_CREATED);
        self::assertSame(Response::HTTP_CREATED, $response->getStatusCode());
    }
}
