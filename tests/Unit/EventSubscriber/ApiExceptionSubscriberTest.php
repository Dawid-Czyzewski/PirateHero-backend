<?php

declare(strict_types=1);

namespace App\Tests\Unit\EventSubscriber;

use App\EventSubscriber\ApiExceptionSubscriber;
use App\Exception\BusinessRuleException;
use App\Exception\OperationForbiddenException;
use App\Exception\ResourceNotFoundException;
use App\Http\ProblemJson;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Exception\ValidationFailedException;

final class ApiExceptionSubscriberTest extends TestCase
{
    private ApiExceptionSubscriber $subscriber;

    protected function setUp(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $this->subscriber = new ApiExceptionSubscriber($logger, 'test');
    }

    public function testBusinessRuleExceptionMapsTo400ProblemJson(): void
    {
        $response = $this->dispatchApiException(new BusinessRuleException('notEnoughEnergy'));

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $body = json_decode($response->getContent(), true);
        self::assertSame('notEnoughEnergy', $body['detail']);
        self::assertSame('BusinessRuleException', $body['extensions']['exception']);
    }

    public function testResourceNotFoundExceptionMapsTo404(): void
    {
        $response = $this->dispatchApiException(new ResourceNotFoundException('missionNotFound'));

        self::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $body = json_decode($response->getContent(), true);
        self::assertSame('missionNotFound', $body['detail']);
    }

    public function testOperationForbiddenExceptionMapsTo403(): void
    {
        $response = $this->dispatchApiException(new OperationForbiddenException('shipMembershipRequired'));

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        $body = json_decode($response->getContent(), true);
        self::assertSame('shipMembershipRequired', $body['detail']);
    }

    public function testValidationFailedExceptionChainMapsTo422WithViolations(): void
    {
        $violations = new ConstraintViolationList([
            new ConstraintViolation('Email is required.', '', [], null, 'email', null),
        ]);
        $exception = HttpException::fromStatusCode(
            Response::HTTP_UNPROCESSABLE_ENTITY,
            'Email is required.',
            new ValidationFailedException(null, $violations),
        );

        $response = $this->dispatchApiException($exception);

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
        $body = json_decode($response->getContent(), true);
        self::assertArrayHasKey('violations', $body);
        self::assertSame('email', $body['violations'][0]['propertyPath']);
    }

    public function testNonApiRequestIsIgnored(): void
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = Request::create('/dashboard');
        $event = new ExceptionEvent(
            $kernel,
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            new BusinessRuleException('ignored'),
        );

        $this->subscriber->onKernelException($event);

        self::assertFalse($event->hasResponse());
    }

    public function testAccessDeniedExceptionMapsTo403(): void
    {
        $response = $this->dispatchApiException(new AccessDeniedException('Access Denied.'));

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    public function testAuthenticationExceptionMapsTo401(): void
    {
        $response = $this->dispatchApiException(new BadCredentialsException('JWT Token not found'));

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    private function dispatchApiException(\Throwable $throwable): Response
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = Request::create('/api/test');
        $request->headers->set('Accept', 'application/json');
        $event = new ExceptionEvent(
            $kernel,
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $throwable,
        );

        $this->subscriber->onKernelException($event);

        self::assertTrue($event->hasResponse());
        $response = $event->getResponse();
        self::assertNotNull($response);
        self::assertStringContainsString(ProblemJson::CONTENT_TYPE, (string) $response->headers->get('Content-Type'));

        return $response;
    }
}
