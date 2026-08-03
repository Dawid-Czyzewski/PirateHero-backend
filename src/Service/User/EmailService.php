<?php

declare(strict_types=1);

namespace App\Service\User;

use App\Application\Port\SendPasswordResetEmailPort;
use App\Application\Port\SendRegistrationEmailPort;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

class EmailService implements SendRegistrationEmailPort, SendPasswordResetEmailPort
{
    public function __construct(
        private MailerInterface $mailer,
        private Environment $twig,
        #[Autowire('%mailer_from%')]
        private string $mailFrom,
        #[Autowire('%mailer_hidden_copy%')]
        private string $mailerHiddenCopy = '',
    ) {
    }

    public function sendRegistrationEmail(string $to, string $username, string $activateToken, string $frontendUrl): void
    {
        $emailContent = $this->twig->render('emails/registrationEmail.html.twig', [
            'username' => $username,
            'activateToken' => $activateToken,
            'frontendUrl' => $frontendUrl,
        ]);

        $email = $this->withHiddenCopy(
            (new Email())
                ->from($this->mailFrom)
                ->to($to)
                ->subject('Activate Your Fame Fighters Account')
                ->html($emailContent)
        );

        $this->mailer->send($email);
    }

    public function sendPasswordResetEmail(
        string $to,
        string $username,
        string $resetToken,
        string $frontendUrl,
    ): void {
        $emailContent = $this->twig->render('emails/passwordResetEmail.html.twig', [
            'username' => $username,
            'resetToken' => $resetToken,
            'frontendUrl' => $frontendUrl,
        ]);

        $email = $this->withHiddenCopy(
            (new Email())
                ->from($this->mailFrom)
                ->to($to)
                ->subject('Reset Your Fame Fighters Password')
                ->html($emailContent)
        );

        $this->mailer->send($email);
    }

    private function withHiddenCopy(Email $email): Email
    {
        $bcc = trim($this->mailerHiddenCopy);
        if ($bcc !== '') {
            $email->bcc($bcc);
        }

        return $email;
    }
}
