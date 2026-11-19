<?php

declare(strict_types=1);

namespace App\Tests\Unit\Character\Domain\Model;

use App\Character\Domain\Exception\InvalidCharacterData;
use App\Character\Domain\Model\Wallet;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class WalletTest extends TestCase
{
    public function testInitialWalletHasStartingBalances(): void
    {
        $wallet = Wallet::initial();

        self::assertSame(100, $wallet->onions());
        self::assertSame(10, $wallet->goldTeeth());
        self::assertSame(0, $wallet->reach());
        self::assertSame(0, $wallet->medals());
    }

    public function testAcceptsZeroBalances(): void
    {
        $wallet = new Wallet(onions: 0, goldTeeth: 0, reach: 0, medals: 0);

        self::assertSame(0, $wallet->onions());
        self::assertSame(0, $wallet->medals());
    }

    /**
     * @return iterable<string, array{array{int, int, int, int}, string}>
     */
    public static function negativeBalances(): iterable
    {
        yield 'onions' => [[-1, 0, 0, 0], 'onions'];
        yield 'gold teeth' => [[0, -1, 0, 0], 'goldTeeth'];
        yield 'reach' => [[0, 0, -1, 0], 'reach'];
        yield 'medals' => [[0, 0, 0, -1], 'medals'];
    }

    /**
     * @param array{int, int, int, int} $values
     */
    #[DataProvider('negativeBalances')]
    public function testRejectsNegativeBalance(array $values, string $field): void
    {
        try {
            new Wallet(...$values);
            self::fail('Expected InvalidCharacterData');
        } catch (InvalidCharacterData $e) {
            self::assertSame($field, $e->field);
        }
    }
}
