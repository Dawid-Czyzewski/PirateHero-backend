<?php

declare(strict_types=1);

namespace App\Service\ShopBoosters;

final class ShopBoosterPublicCodeResolve
{
    /** @var array<string, string> mis_1 → m1, … */
    private const NEW_TO_LEGACY = [
        'mis_1' => 'm1', 'mis_2' => 'm2', 'mis_3' => 'm3',
        'trn_1' => 't1', 'trn_2' => 't2', 'trn_3' => 't3',
        'wrk_1' => 'w1', 'wrk_2' => 'w2', 'wrk_3' => 'w3',
        'skl_1' => 's1', 'skl_2' => 's2', 'skl_3' => 's3',
    ];

    public static function lookupCandidates(string $publicCode): array
    {
        $c = trim($publicCode);
        if ($c === '') {
            return [];
        }

        $out = [$c];
        if (isset(self::NEW_TO_LEGACY[$c])) {
            $out[] = self::NEW_TO_LEGACY[$c];
        }
        foreach (self::NEW_TO_LEGACY as $newId => $legacyId) {
            if ($legacyId === $c) {
                $out[] = $newId;
            }
        }

        return array_values(array_unique($out));
    }
}
