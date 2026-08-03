<?php

declare(strict_types=1);

namespace App\Http;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;

final class ApiEnvelope
{
    private function __construct()
    {
    }

    public static function success(mixed $data, ?string $messageKey = null): array
    {
        return [
            'data' => $data,
            'meta' => ['message' => $messageKey],
        ];
    }

    public static function jsonResponse(
        mixed $data,
        ?string $messageKey = null,
        int $status = Response::HTTP_OK,
    ): JsonResponse {
        return new JsonResponse(self::success($data, $messageKey), $status);
    }

    public static function jsonResponseSerialized(
        SerializerInterface $serializer,
        mixed $data,
        ?string $messageKey = null,
        int $status = Response::HTTP_OK,
        array $headers = [],
        array $serializerContext = [],
    ): JsonResponse {
        $payload = self::success($data, $messageKey);
        $json = $serializer->serialize($payload, 'json', array_merge([
            'json_encode_options' => JsonResponse::DEFAULT_ENCODING_OPTIONS,
        ], $serializerContext));

        return new JsonResponse($json, $status, $headers, true);
    }
}
