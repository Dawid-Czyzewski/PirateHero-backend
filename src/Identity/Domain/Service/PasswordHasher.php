<?php

declare(strict_types=1);

namespace App\Identity\Domain\Service;

use App\Identity\Domain\Model\HashedPassword;
use App\Identity\Domain\Model\PlainPassword;

interface PasswordHasher
{
    public function hash(PlainPassword $password): HashedPassword;
}
