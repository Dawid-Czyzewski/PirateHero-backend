<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Domain\Constants\WearableUpgradeConstants;
use App\Entity\ItemStatistics;
use App\Entity\User;
use App\Entity\WearableItem;
use App\Enum\WearableItemRarity;
use App\Enum\WearableItemType;
use App\Tests\Functional\ApiWebTestCase;

final class WorkshopUpgradeEndpointsTest extends ApiWebTestCase
{
    public function testUpgradeWithoutItemIdReturnsValidationError(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request('POST', '/api/game-shop/upgrade', [], [], ['CONTENT_TYPE' => 'application/json'], '{}');

        self::assertSame(400, $client->getResponse()->getStatusCode());
        $problem = $this->assertProblemJson($client->getResponse());
        self::assertSame('itemIdRequired', $problem['detail']);
    }

    public function testUpgradeOwnedItemIncreasesLevelAndSpendsGold(): void
    {
        $user = $this->makeUserWithChestItem();
        $item = $this->persistWearableInChest($user);
        $goldBefore = $user->getGold();
        $cost = WearableUpgradeConstants::goldCost(0, WearableItemRarity::COMMON);

        $client = $this->createAuthenticatedClient($user);
        $client->request(
            'POST',
            '/api/game-shop/upgrade',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['itemId' => $item->getId()], JSON_THROW_ON_ERROR),
        );

        self::assertResponseIsSuccessful();
        $decoded = $this->assertJsonEnvelopeSuccess($client->getResponse());
        self::assertSame('itemUpgraded', $decoded['meta']['message'] ?? null);

        $upgrade = $decoded['data']['upgrade'] ?? null;
        self::assertIsArray($upgrade);
        self::assertSame(1, $upgrade['upgradeLevel'] ?? null);
        self::assertSame($cost, $upgrade['goldSpent'] ?? null);
        self::assertSame($goldBefore - $cost, $upgrade['gold'] ?? null);

        $this->entityManager()->clear();
        $reloaded = $this->entityManager()->find(WearableItem::class, $item->getId());
        self::assertNotNull($reloaded);
        self::assertSame(1, $reloaded->getUpgradeLevel());
        self::assertSame($goldBefore - $cost, $this->entityManager()->find(User::class, $user->getId())?->getGold());
    }

    public function testUpgradeUnknownItemReturnsNotFound(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'POST',
            '/api/game-shop/upgrade',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['itemId' => 999_999], JSON_THROW_ON_ERROR),
        );

        self::assertSame(404, $client->getResponse()->getStatusCode());
        $problem = $this->assertProblemJson($client->getResponse());
        self::assertSame('wearableItemNotFound', $problem['detail']);
    }

    public function testSpecializeAtMaxLevelSetsHealth(): void
    {
        $user = $this->makeUserWithChestItem();
        $item = $this->persistWearableInChest($user);
        $item->setUpgradeLevel(3);
        $this->entityManager()->flush();
        $cost = WearableUpgradeConstants::specializationGoldCost(WearableItemRarity::COMMON);
        $goldBefore = $user->getGold();
        $hpBefore = $item->getStatistics()?->getHealthPoints() ?? 0;

        $client = $this->createAuthenticatedClient($user);
        $client->request(
            'POST',
            '/api/game-shop/specialize',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['itemId' => $item->getId(), 'specialization' => 'health'], JSON_THROW_ON_ERROR),
        );

        self::assertResponseIsSuccessful();
        $decoded = $this->assertJsonEnvelopeSuccess($client->getResponse());
        self::assertSame('itemSpecialized', $decoded['meta']['message'] ?? null);
        $spec = $decoded['data']['specialize'] ?? null;
        self::assertIsArray($spec);
        self::assertSame('health', $spec['specialization'] ?? null);
        self::assertSame($cost, $spec['goldSpent'] ?? null);
        self::assertSame($goldBefore - $cost, $spec['gold'] ?? null);

        $this->entityManager()->clear();
        $reloaded = $this->entityManager()->find(WearableItem::class, $item->getId());
        self::assertNotNull($reloaded);
        self::assertSame('health', $reloaded->getSpecialization());
        self::assertSame($hpBefore + WearableUpgradeConstants::HEALTH_BONUS, $reloaded->getStatistics()?->getHealthPoints());
    }

    private function makeUserWithChestItem(): User
    {
        $user = $this->makePersistedActivatedUser();
        $this->ensureUserStorage($user);

        return $user;
    }

    private function persistWearableInChest(User $user): WearableItem
    {
        $em = $this->entityManager();
        $stats = (new ItemStatistics())
            ->setStrongPoints(4)
            ->setAgilityPoints(1)
            ->setCriticalChancePoints(1)
            ->setHealthPoints(2);

        $item = (new WearableItem())
            ->setName('Test helm')
            ->setType(WearableItemType::Helmet)
            ->setRarity(WearableItemRarity::COMMON)
            ->setPrice(25)
            ->setStatistics($stats);

        $em->persist($stats);
        $em->persist($item);
        $em->flush();

        $storage = $user->getStorage();
        self::assertNotNull($storage);
        foreach ($storage->getSlots() as $slot) {
            if ($slot->getItem() === null) {
                $slot->setItem($item);
                $em->persist($slot);
                $em->flush();
                break;
            }
        }

        return $item;
    }
}
