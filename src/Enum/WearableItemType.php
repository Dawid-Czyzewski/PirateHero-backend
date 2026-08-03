<?php

declare(strict_types=1);

namespace App\Enum;

enum WearableItemType: string
{
    public const SHOP_OFFER_CELL_COUNT = 9;

    case Helmet = 'helmet';
    case Weapon = 'weapon';
    case Armor = 'armor';
    case Shield = 'shield';
    case Gloves = 'gloves';
    case Boots = 'boots';
    case Amulet = 'amulet';
    case Ring = 'ring';
    case Potions = 'potions';

    public static function orderedCases(): array
    {
        return [
            self::Helmet,
            self::Weapon,
            self::Armor,
            self::Amulet,
            self::Ring,
            self::Boots,
        ];
    }

    public static function shopOfferSlotOrder(): array
    {
        return self::orderedCases();
    }

    public static function randomShopOfferType(): self
    {
        $order = self::shopOfferSlotOrder();

        return $order[random_int(0, \count($order) - 1)];
    }

    public static function fromLegacyOrSelf(string $value): self
    {
        $normalized = strtoupper(trim($value));

        return match ($normalized) {
            'HEAD' => self::Helmet,
            'HANDS' => self::Weapon,
            'SHIRT', 'PANTS' => self::Armor,
            'SHOES' => self::Boots,
            default => self::from(strtolower($value)),
        };
    }
}
