<?php

declare(strict_types=1);

namespace App\Service\User;

use App\Application\Port\SendPasswordResetEmailPort;
use App\Exception\BusinessRuleException;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class RequestPasswordResetService
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
        private SendPasswordResetEmailPort $sendPasswordResetEmail,
        #[Autowire('%frontend_url%')]
        private string $frontendUrl,
        #[Autowire('%env(MAILER_DSN)%')]
        private string $mailerDsn,
        private LoggerInterface $logger,
    ) {
    }

    public function requestReset(string $email): void
    {
        $normalized = trim($email);
        if ($normalized === '') {
            throw new BusinessRuleException('passwordResetEmailRequired');
        }
        if (!filter_var($normalized, \FILTER_VALIDATE_EMAIL)) {
            throw new BusinessRuleException('passwordResetEmailInvalid');
        }

        $user = $this->userRepository->findOneByEmailIgnoreCase($normalized);
        if ($user === null) {
            $this->logger->info('Password reset: no account for this email (response still success).');

            return;
        }

        if ($user->getActivateToken() !== null) {
            $this->logger->warning('Password reset: account not activated yet — email not sent.', [
                'email' => $user->getEmail(),
            ]);

            return;
        }

        $expiresAt = new \DateTimeImmutable('+1 hour');
        $token = $user->issuePasswordResetToken($expiresAt);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $resetUrl = rtrim($this->frontendUrl, '/').'/auth/reset-password/'.$token;

        try {
            $this->sendPasswordResetEmail->sendPasswordResetEmail(
                $user->getEmail(),
                $user->getUsername(),
                $token,
                $this->frontendUrl,
            );
        } catch (\Throwable $e) {
            $this->logger->error('Password reset email failed to send.', [
                'email' => $user->getEmail(),
                'exception' => $e,
            ]);

            throw $e;
        }

        if (str_starts_with($this->mailerDsn, 'null://')) {
            $this->logger->warning(
                'MAILER_DSN=null:// — email was NOT delivered. Configure SMTP in .env.local. Reset link: {url}',
                ['url' => $resetUrl],
            );
        }
    }
}
