<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\User;

use App\Service\User\EmailService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

final class EmailServiceTest extends TestCase
{
    public function testSendRegistrationEmailRendersTemplateAndSends(): void
    {
        $twig = $this->createMock(Environment::class);
        $twig->expects(self::once())
            ->method('render')
            ->with(
                'emails/registrationEmail.html.twig',
                self::callback(static fn (array $ctx): bool => $ctx['username'] === 'alice'
                    && $ctx['activateToken'] === 'tok'
                    && $ctx['frontendUrl'] === 'https://app.example'),
            )
            ->willReturn('<html>ok</html>');

        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::once())
            ->method('send')
            ->with(self::callback(static function ($email): bool {
                return $email instanceof Email
                    && $email->getTo()[0]->getAddress() === 'a@b.c'
                    && $email->getBcc()[0]->getAddress() === 'hidden@example.com'
                    && str_contains((string) $email->getHtmlBody(), 'ok');
            }));

        $service = new EmailService($mailer, $twig, 'no-reply@test.local', 'hidden@example.com');
        $service->sendRegistrationEmail('a@b.c', 'alice', 'tok', 'https://app.example');
    }

    public function testSendRegistrationEmailWithoutHiddenCopyWhenUnset(): void
    {
        $twig = $this->createMock(Environment::class);
        $twig->method('render')->willReturn('<html>ok</html>');

        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::once())
            ->method('send')
            ->with(self::callback(static function ($email): bool {
                return $email instanceof Email && $email->getBcc() === [];
            }));

        $service = new EmailService($mailer, $twig, 'no-reply@test.local', '');
        $service->sendRegistrationEmail('a@b.c', 'alice', 'tok', 'https://app.example');
    }
}
