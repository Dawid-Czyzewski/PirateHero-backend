<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Exception\BusinessRuleException;
use App\Exception\DomainException;
use App\Exception\OperationForbiddenException;
use App\Exception\ResourceNotFoundException;
use App\Http\ProblemJson;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Validator\Exception\ValidationFailedException;

final class ApiExceptionSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private LoggerInterface $logger,
        #[Autowire('%kernel.environment%')]
        private string $environment,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException', 255],
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $request = $event->getRequest();
        if (!$this->isApiRequest($request)) {
            return;
        }

        $throwable = $event->getThrowable();
        $status = $this->resolveStatusCode($throwable);
        $detail = $this->resolveDetail($throwable, $status);
        $violations = $this->extractViolations($throwable);
        $extensions = $this->buildDomainExtensions($throwable);

        if ($status >= Response::HTTP_INTERNAL_SERVER_ERROR) {
            $this->logger->error($throwable->getMessage(), [
                'exception' => $throwable,
                'path' => $request->getPathInfo(),
            ]);
        }

        $response = ProblemJson::response($status, $detail, $violations, $extensions);
        $this->mergeHttpExceptionHeaders($response, $throwable);
        $event->setResponse($response);
    }

    private function isApiRequest(Request $request): bool
    {
        $path = $request->getPathInfo();
        if (!str_starts_with($path, '/api')) {
            return false;
        }
        $accept = $request->headers->get('Accept', '');
        if ($accept !== '' && str_contains($accept, 'text/html') && !str_contains($accept, 'application/json')) {
            return false;
        }

        return true;
    }

    private function resolveStatusCode(\Throwable $e): int
    {
        if ($e instanceof ResourceNotFoundException) {
            return Response::HTTP_NOT_FOUND;
        }
        if ($e instanceof OperationForbiddenException) {
            return Response::HTTP_FORBIDDEN;
        }
        if ($e instanceof AuthenticationException) {
            return Response::HTTP_UNAUTHORIZED;
        }
        if ($e instanceof AccessDeniedException) {
            return Response::HTTP_FORBIDDEN;
        }
        if ($e instanceof BusinessRuleException) {
            return Response::HTTP_BAD_REQUEST;
        }
        if ($e instanceof HttpExceptionInterface) {
            return $e->getStatusCode();
        }

        return Response::HTTP_INTERNAL_SERVER_ERROR;
    }

    private function resolveDetail(\Throwable $e, int $status): string
    {
        if ($status >= Response::HTTP_INTERNAL_SERVER_ERROR && $this->environment === 'prod') {
            return 'Internal server error';
        }

        return $e->getMessage() !== '' ? $e->getMessage() : 'Error';
    }

    private function buildDomainExtensions(\Throwable $e): array
    {
        if (!$e instanceof DomainException) {
            return [];
        }

        $short = (new \ReflectionClass($e))->getShortName();

        return [
            'exception' => $short,
        ];
    }

    private function extractViolations(\Throwable $e): array
    {
        $current = $e;
        while ($current !== null) {
            if ($current instanceof ValidationFailedException) {
                $out = [];
                foreach ($current->getViolations() as $v) {
                    $out[] = [
                        'propertyPath' => $v->getPropertyPath(),
                        'message' => $v->getMessage(),
                        'code' => $v->getCode(),
                    ];
                }

                return $out;
            }
            $current = $current->getPrevious();
        }

        return [];
    }

    private function mergeHttpExceptionHeaders(Response $response, \Throwable $e): void
    {
        if (!$e instanceof HttpExceptionInterface) {
            return;
        }
        foreach ($e->getHeaders() as $name => $values) {
            if (!\is_array($values)) {
                $response->headers->set($name, $values);

                continue;
            }
            if ($values !== []) {
                $response->headers->set($name, $values[0]);
            }
        }
        $response->headers->set('Content-Type', ProblemJson::CONTENT_TYPE);
    }
}
