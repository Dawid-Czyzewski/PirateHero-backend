<?php

declare(strict_types=1);

namespace App\Application\Port;

interface SendPasswordResetEmailPort
{
    public function sendPasswordResetEmail(
        string $to,
        string $username,
        string $resetToken,
        string $frontendUrl,
    ): void;
}
