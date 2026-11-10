<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared;

use App\Shared\Presentation\Http\HealthController;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\ConnectionException;
use Doctrine\DBAL\Result;
use PHPUnit\Framework\TestCase;

final class HealthControllerTest extends TestCase
{
    public function testReportsOkWhenDatabaseAnswers(): void
    {
        $result = $this->createStub(Result::class);
        $result->method('fetchOne')->willReturn(1);
        $connection = $this->createStub(Connection::class);
        $connection->method('executeQuery')->willReturn($result);

        $response = (new HealthController($connection))();

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('{"status":"ok","db":"ok"}', $response->getContent());
    }

    public function testReports503WhenDatabaseIsDown(): void
    {
        $connection = $this->createStub(Connection::class);
        $connection->method('executeQuery')->willThrowException($this->createStub(ConnectionException::class));

        $response = (new HealthController($connection))();

        self::assertSame(503, $response->getStatusCode());
        self::assertSame('{"status":"error","db":"error"}', $response->getContent());
    }
}
