<?php

declare(strict_types=1);

namespace App\Service\Progression;

final class MissionTitlePool
{
    private const OFFER_COUNT = 5;

    /**
     * @var list<string>
     */
    private const TITLES = [
        'mission.pirate_smugglers_cove',
        'mission.pirate_galleon_plunder',
        'mission.pirate_skull_harbor_raid',
        'mission.pirate_cursed_compass',
        'mission.pirate_admirals_bounty',
        'mission.pirate_black_flag_job',
        'mission.pirate_kraken_deep',
        'mission.pirate_treasure_convoy',
        'mission.coast_patrol',
        'mission.convoy_protection',
        'mission.corsair_hunt',
        'mission.merchant_escort',
        'mission.island_recon',
        'mission.ruins_survey',
        'mission.relic_search',
        'mission.temple_guard',
        'mission.deep_expedition',
        'mission.atlantis_map',
        'mission.tentacle_tracking',
        'mission.nest_search',
        'mission.scale_gathering',
        'mission.monster_research',
        'mission.crew_training',
        'mission.fleet_patrol',
        'mission.weapon_delivery',
        'mission.garrison_support',
        'mission.timber_transport',
        'mission.spice_trade',
        'mission.loot_sale',
        'mission.treasure_hunt',
        'mission.emperor_audience',
        'mission.leviathan_relic',
        'mission.abyss_voyage',
        'mission.final_expedition',
        'mission.great_battle',
        'mission.fog_bank_ambush',
        'mission.shipyard_sabotage',
        'mission.siren_coast_watch',
        'mission.coral_reef_charting',
        'mission.sunken_vault_raid',
    ];

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return self::TITLES;
    }

    /**
     * @return list<string>
     */
    public static function pickOffers(): array
    {
        $pool = self::TITLES;
        $indices = range(0, \count($pool) - 1);
        shuffle($indices);
        $chosen = \array_slice($indices, 0, self::OFFER_COUNT);

        return array_map(static fn (int $i): string => $pool[$i], $chosen);
    }
}
