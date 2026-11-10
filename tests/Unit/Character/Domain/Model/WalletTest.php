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

        self::assertSame(100, $wallet->cebuliony());
        self::assertSame(10, $wallet->zloteZeby());
        self::assertSame(100, $wallet->kondycha());
        self::assertSame(0, $wallet->zasiegi());
        self::assertSame(0, $wallet->medale());
    }

    public function testAcceptsZeroBalances(): void
    {
        $wallet = new Wallet(cebuliony: 0, zloteZeby: 0, kondycha: 0, zasiegi: 0, medale: 0);

        self::assertSame(0, $wallet->cebuliony());
        self::assertSame(0, $wallet->kondycha());
    }

    /**
     * @return iterable<string, array{array{int, int, int, int, int}, string}>
     */
    public static function negativeBalances(): iterable
    {
        yield 'cebuliony' => [[-1, 0, 0, 0, 0], 'cebuliony'];
        yield 'zlote zeby' => [[0, -1, 0, 0, 0], 'zloteZeby'];
        yield 'kondycha' => [[0, 0, -1, 0, 0], 'kondycha'];
        yield 'zasiegi' => [[0, 0, 0, -1, 0], 'zasiegi'];
        yield 'medale' => [[0, 0, 0, 0, -1], 'medale'];
    }

    /**
     * @param array{int, int, int, int, int} $values
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
