<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\ShopBoosters;

use App\Service\ShopBoosters\ShopBoosterEffectParser;
use PHPUnit\Framework\TestCase;

final class ShopBoosterEffectParserTest extends TestCase
{
    /**
     * @dataProvider trainingEffectCases
     */
    public function testParseTrainingFlatBonus(string $effect, int $expected): void
    {
        $parser = new ShopBoosterEffectParser();
        self::assertSame($expected, $parser->parseTrainingFlatBonus($effect));
    }

    /**
     * @return iterable<string, array{string, int}>
     */
    public static function trainingEffectCases(): iterable
    {
        yield 'trn_1' => ['+5 pkt treningu', 5];
        yield 'trn_2' => ['+15 pkt treningu', 15];
        yield 'trn_3' => ['+40 pkt treningu', 40];
        yield 'missions unrelated' => ['+5% PD i +5% złota z misji', 0];
        yield 'empty' => ['', 0];
    }

    /**
     * @dataProvider percentFractionCases
     */
    public function testParseFirstPercentFraction(string $effect, float $expected): void
    {
        $parser = new ShopBoosterEffectParser();
        self::assertEqualsWithDelta($expected, $parser->parseFirstPercentFraction($effect), \PHP_FLOAT_EPSILON);
    }

    /**
     * @return iterable<string, array{string, float}>
     */
    public static function percentFractionCases(): iterable
    {
        yield 'missions' => ['+5% PD i +5% złota z misji', 0.05];
        yield 'percent only' => ['+5%', 0.05];
        yield 'work' => ['+15% złota z pracy', 0.15];
        yield 'skills' => ['+40% atrybutów', 0.40];
        yield 'no percent' => ['+5 pkt treningu', 0.0];
    }
}
