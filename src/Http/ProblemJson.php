<?php

declare(strict_types=1);

namespace App\Http;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class ProblemJson
{
    public const CONTENT_TYPE = 'application/problem+json; charset=utf-8';

    public static function body(
        int $status,
        string $detail,
        array $violations = [],
        array $extensions = [],
        ?string $type = null,
    ): array {
        $title = Response::$statusTexts[$status] ?? 'Error';
        $resolvedType = $type ?? match ($status) {
            Response::HTTP_UNPROCESSABLE_ENTITY => 'https://symfony.com/errors/validation',
            default => 'about:blank',
        };

        $body = [
            'type' => $resolvedType,
            'title' => $title,
            'status' => $status,
            'detail' => $detail,
        ];

        if ($violations !== []) {
            $body['violations'] = $violations;
        }

        if ($extensions !== []) {
            $body['extensions'] = $extensions;
        }

        return $body;
    }

    public static function response(
        int $status,
        string $detail,
        array $violations = [],
        array $extensions = [],
    ): JsonResponse {
        return new JsonResponse(
            self::body($status, $detail, $violations, $extensions),
            $status,
            ['Content-Type' => self::CONTENT_TYPE],
        );
    }
}
