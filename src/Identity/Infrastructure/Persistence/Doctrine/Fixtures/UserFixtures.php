<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Persistence\Doctrine\Fixtures;

use App\Identity\Application\Command\RegisterUser\RegisterUserCommand;
use App\Identity\Application\Command\RegisterUser\RegisterUserHandler;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

final class UserFixtures extends Fixture
{
    public const DEMO_PASSWORD = 'password123';

    public function __construct(private readonly RegisterUserHandler $registerUser)
    {
    }

    public function load(ObjectManager $manager): void
    {
        ($this->registerUser)(new RegisterUserCommand('demo@freaklegends.local', 'DemoFreak', self::DEMO_PASSWORD));

        foreach (['Cebulak', 'Mlotek', 'Ziomal', 'Szpachla', 'Kozak'] as $nick) {
            ($this->registerUser)(new RegisterUserCommand(strtolower($nick).'@freaklegends.local', $nick, self::DEMO_PASSWORD));
        }
    }
}
