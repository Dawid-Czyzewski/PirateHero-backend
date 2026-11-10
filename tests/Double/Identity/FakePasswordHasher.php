<?php

declare(strict_types=1);

namespace App\Tests\Double\Identity;

use App\Identity\Domain\Model\HashedPassword;
use App\Identity\Domain\Model\PlainPassword;
use App\Identity\Domain\Service\PasswordHasher;

final class FakePasswordHasher implements PasswordHasher
{
    public function hash(PlainPassword $password): HashedPassword
    {
        return new HashedPassword('hashed:'.$password->reveal());
    }
}
