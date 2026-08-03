<?php

declare(strict_types=1);

namespace App\Service\Ship;

use App\Domain\Constants\ShipConstants;
use App\Entity\ShipUpgradeLevelCost;
use App\Repository\ShipUpgradeLevelCostRepository;

class ShipUpgradePricingService
{
    public function __construct(
        private readonly ShipUpgradeLevelCostRepository $repository,
    ) {
    }

    /**
     * @return array{gold: int, diamonds: int}
     */
    public function getCostForTargetLevel(string $upgradeType, int $targetLevel): array
    {
        try {
            $row = $this->repository->findOneBy(['upgradeType' => $upgradeType, 'targetLevel' => $targetLevel]);
        } catch (\Throwable) {
            return self::legacyCost($targetLevel, self::maxForType($upgradeType));
        }

        if (!$row instanceof ShipUpgradeLevelCost) {
            return self::legacyCost($targetLevel, self::maxForType($upgradeType));
        }

        return [
            'gold' => $row->getGold(),
            'diamonds' => $row->getDiamonds(),
        ];
    }

    /**
     * @return array{
     *     skills: list<array{level: int, gold: int, diamonds: int}>,
     *     work: list<array{level: int, gold: int, diamonds: int}>,
     *     missions: list<array{level: int, gold: int, diamonds: int}>,
     *     hull: list<array{level: int, gold: int, diamonds: int}>
     * }
     */
    public function getPricingMatrixForApi(): array
    {
        $matrix = [
            'skills' => [],
            'work' => [],
            'missions' => [],
            'hull' => [],
        ];

        try {
            $rows = $this->repository->findBy([], ['upgradeType' => 'ASC', 'targetLevel' => 'ASC']);
        } catch (\Throwable) {
            return self::defaultMatrixFromLegacy();
        }

        if ($rows === []) {
            return self::defaultMatrixFromLegacy();
        }

        foreach ($rows as $row) {
            $t = $row->getUpgradeType();
            if (!isset($matrix[$t])) {
                continue;
            }
            $matrix[$t][] = [
                'level' => $row->getTargetLevel(),
                'gold' => $row->getGold(),
                'diamonds' => $row->getDiamonds(),
            ];
        }

        return $matrix;
    }

    /**
     * @return array{
     *     skills: list<array{level: int, gold: int, diamonds: int}>,
     *     work: list<array{level: int, gold: int, diamonds: int}>,
     *     missions: list<array{level: int, gold: int, diamonds: int}>,
     *     hull: list<array{level: int, gold: int, diamonds: int}>
     * }
     */
    private static function defaultMatrixFromLegacy(): array
    {
        $branch = static function (int $max): array {
            $out = [];
            for ($L = 1; $L <= $max; ++$L) {
                $c = self::legacyCost($L, $max);
                $out[] = ['level' => $L, 'gold' => $c['gold'], 'diamonds' => $c['diamonds']];
            }

            return $out;
        };

        return [
            'skills' => $branch(ShipConstants::MAX_UPGRADE_BY_TYPE['skills']),
            'work' => $branch(ShipConstants::MAX_UPGRADE_BY_TYPE['work']),
            'missions' => $branch(ShipConstants::MAX_UPGRADE_BY_TYPE['missions']),
            'hull' => $branch(ShipConstants::MAX_UPGRADE_BY_TYPE['hull']),
        ];
    }

    /**
     * @return array{gold: int, diamonds: int}
     */
    public static function legacyCost(int $targetLevel, int $maxLevel): array
    {
        $L = max(1, $targetLevel);
        $max = max(1, $maxLevel);
        $goldOnlyThrough = intdiv($max, 2);
        $diamonds = $L <= $goldOnlyThrough
            ? 0
            : ShipConstants::LEGACY_BASE_DIAMONDS + ($L - 1) * ShipConstants::LEGACY_DIAMONDS_STEP;

        return [
            'gold' => ShipConstants::LEGACY_BASE_GOLD + ($L - 1) * ShipConstants::LEGACY_GOLD_STEP,
            'diamonds' => $diamonds,
        ];
    }

    private static function maxForType(string $upgradeType): int
    {
        return ShipConstants::MAX_UPGRADE_BY_TYPE[$upgradeType] ?? ShipConstants::MAX_UPGRADE_LEVEL;
    }
}
