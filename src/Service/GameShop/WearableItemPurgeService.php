<?php

declare(strict_types=1);

namespace App\Service\GameShop;

use Doctrine\ORM\EntityManagerInterface;

class WearableItemPurgeService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @return array{items: int, statistics: int}
     */
    public function purgeAllInstances(): array
    {
        $conn = $this->entityManager->getConnection();

        $itemRows = $conn->fetchAllAssociative('SELECT id, statistics_id FROM wearable_item');
        if ($itemRows === []) {
            return ['items' => 0, 'statistics' => 0];
        }

        $itemIds = array_map(static fn (array $r): int => (int) $r['id'], $itemRows);
        $statIds = array_values(array_filter(array_map(
            static fn (array $r): ?int => isset($r['statistics_id']) ? (int) $r['statistics_id'] : null,
            $itemRows
        )));

        $inItems = implode(',', array_fill(0, \count($itemIds), '?'));

        $conn->executeStatement(
            "UPDATE user_equipment_slot SET wearable_item_id = NULL WHERE wearable_item_id IN ($inItems)",
            $itemIds
        );
        $conn->executeStatement(
            "UPDATE user_storage_slot SET item_id = NULL WHERE item_id IN ($inItems)",
            $itemIds
        );
        $conn->executeStatement(
            "UPDATE quest_template SET reward_item_id = NULL WHERE reward_item_id IN ($inItems)",
            $itemIds
        );

        $storeItemIds = $conn->fetchFirstColumn(
            "SELECT item_id FROM store_slot WHERE item_id IN ($inItems)",
            $itemIds
        );
        if ($storeItemIds !== []) {
            $conn->executeStatement(
                "UPDATE store_slot SET item_id = NULL WHERE item_id IN ($inItems)",
                $itemIds
            );
        }

        $conn->executeStatement("DELETE FROM wearable_item WHERE id IN ($inItems)", $itemIds);

        $statsDeleted = 0;
        if ($statIds !== []) {
            $inStats = implode(',', array_fill(0, \count($statIds), '?'));
            $statsDeleted = $conn->executeStatement(
                "DELETE FROM item_statistics WHERE id IN ($inStats)",
                $statIds
            );
        }

        return ['items' => \count($itemIds), 'statistics' => $statsDeleted];
    }
}
