<?php

declare(strict_types=1);

namespace App\Application\Port;

interface SendRegistrationEmailPort
{
    public function sendRegistrationEmail(string $to, string $username, string $activateToken, string $frontendUrl): void;
}
