<?php

declare(strict_types=1);

namespace App\Tests\TestDoubles;

use App\Entity\Level;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

final class UserStubFactory
{
    /**
     * @param array{
     *     prefix?: string,
     *     levelName?: string,
     *     expToNextLevel?: int,
     *     gold?: int,
     *     diamonds?: int,
     *     energyPoints?: int,
     *     trainingPoints?: int,
     *     duelPoints?: int,
     *     famePoints?: int,
     *     emailDomain?: string,
     * } $options
     */
    public static function create(array $options = []): User
    {
        $prefix = $options['prefix'] ?? 'user';
        $levelName = $options['levelName'] ?? '1';
        $expToNextLevel = $options['expToNextLevel'] ?? 100;
        $emailDomain = $options['emailDomain'] ?? 'test.local';

        $level = (new Level())
            ->setName($levelName)
            ->setExpToNextLevel($expToNextLevel);

        return (new User())
            ->setEmail(sprintf('%s_%s@%s', $prefix, bin2hex(random_bytes(3)), $emailDomain))
            ->setUsername(sprintf('%s_%s', $prefix, bin2hex(random_bytes(3))))
            ->setPassword('x')
            ->setLevel($level)
            ->setGold($options['gold'] ?? 0)
            ->setDiamonds($options['diamonds'] ?? 0)
            ->setEnergyPoints($options['energyPoints'] ?? 100)
            ->setTrainingPoints($options['trainingPoints'] ?? 10)
            ->setDuelPoints($options['duelPoints'] ?? 10)
            ->setFamePoints($options['famePoints'] ?? 0);
    }

    /** @param array<string, mixed> $methodReturns */
    public static function mock(TestCase $testCase, array $methodReturns = []): User
    {
        return $testCase->createConfiguredMock(User::class, $methodReturns);
    }
}
