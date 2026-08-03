<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\User;
use App\Exception\BusinessRuleException;
use PHPUnit\Framework\TestCase;

final class UserDomainApiTest extends TestCase
{
    public function testSpendGoldThrowsWhenInsufficient(): void
    {
        $user = new User();
        $user->setGold(10);

        $this->expectException(BusinessRuleException::class);
        $this->expectExceptionMessage('notEnoughGold');
        $user->spendGold(11);
    }

    public function testSpendGoldDeductsBalance(): void
    {
        $user = new User();
        $user->setGold(50);

        $user->spendGold(20);

        self::assertSame(30, $user->getGold());
    }

    public function testAddGoldIncreasesBalance(): void
    {
        $user = new User();
        $user->setGold(5);

        $user->addGold(15);

        self::assertSame(20, $user->getGold());
    }

    public function testAddGoldRejectsNegativeAmount(): void
    {
        $user = new User();
        $user->setGold(5);

        $this->expectException(\InvalidArgumentException::class);
        $user->addGold(-1);
    }

    public function testSpendEnergyThrowsWhenInsufficient(): void
    {
        $user = new User();
        $user->setEnergyPoints(3);

        $this->expectException(BusinessRuleException::class);
        $this->expectExceptionMessage('notEnoughEnergy');
        $user->spendEnergy(4);
    }

    public function testSpendEnergyDeductsPoints(): void
    {
        $user = new User();
        $user->setEnergyPoints(10);

        $user->spendEnergy(4);

        self::assertSame(6, $user->getEnergyPoints());
    }

    public function testRestoreEnergyIncreasesPoints(): void
    {
        $user = new User();
        $user->setEnergyPoints(10);

        $user->restoreEnergy(5);

        self::assertSame(15, $user->getEnergyPoints());
    }

    public function testAddFamePointsIncreasesBalance(): void
    {
        $user = new User();
        $user->setFamePoints(100);

        $user->addFamePoints(25);

        self::assertSame(125, $user->getFamePoints());
    }

    public function testAddFamePointsClampsAtZero(): void
    {
        $user = new User();
        $user->setFamePoints(10);

        $user->addFamePoints(-50);

        self::assertSame(0, $user->getFamePoints());
    }

    public function testSetFamePointsAlsoClampsAtZero(): void
    {
        $user = new User();
        $user->setFamePoints(-5);

        self::assertSame(0, $user->getFamePoints());
    }
}
