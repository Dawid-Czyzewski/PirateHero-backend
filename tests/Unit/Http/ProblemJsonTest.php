<?php

declare(strict_types=1);

namespace App\Tests\Unit\Http;

use App\Http\ProblemJson;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

final class ProblemJsonTest extends TestCase
{
    public function testBodyContainsRfc7807CoreFields(): void
    {
        $body = ProblemJson::body(Response::HTTP_FORBIDDEN, 'accountNotActivated');
        self::assertSame('about:blank', $body['type']);
        self::assertSame(Response::HTTP_FORBIDDEN, $body['status']);
        self::assertSame('accountNotActivated', $body['detail']);
        self::assertArrayHasKey('title', $body);
    }

    public function testResponseSetsProblemContentType(): void
    {
        $response = ProblemJson::response(Response::HTTP_BAD_REQUEST, 'invalidInput');
        self::assertStringContainsString('application/problem+json', (string) $response->headers->get('Content-Type'));
        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }
}
