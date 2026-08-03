<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\QuestTemplate;
use App\Entity\User;
use App\Entity\UserQuest;
use App\Enum\QuestCategory;
use App\Enum\QuestRewardType;
use App\Service\Economy\StorageService;
use App\Tests\Functional\ApiWebTestCase;
use Doctrine\ORM\EntityManagerInterface;

final class QuestTaskControllerTest extends ApiWebTestCase
{
    public function testGetUserQuestsReturnsCompletedHistoryWithEachRewardType(): void
    {
        $user = $this->makeUserWithStorage();
        $em = $this->entityManager();

        $gold = $this->makeInactiveTemplate($em, QuestCategory::GOLD_SPENT, QuestRewardType::GOLD, 100, 101);
        $exp = $this->makeInactiveTemplate($em, QuestCategory::LEVEL_UP, QuestRewardType::EXPERIENCE, 50, 102);
        $gems = $this->makeInactiveTemplate($em, QuestCategory::FIGHTS_WON, QuestRewardType::diamonds, 2, 103);
        $item = $this->makeInactiveTemplate($em, QuestCategory::FIGHTS_LOST, QuestRewardType::ITEM, 1, 104);

        $this->persistCompletedClaimedUserQuest($user, $gold);
        $this->persistCompletedClaimedUserQuest($user, $exp);
        $this->persistCompletedClaimedUserQuest($user, $gems);
        $this->persistCompletedClaimedUserQuest($user, $item);

        $client = $this->createAuthenticatedClient($user);
        $client->request('GET', '/api/user_quests');

        self::assertResponseIsSuccessful();
        $decoded = $this->assertJsonEnvelopeSuccess($client->getResponse());
        $quests = $decoded['data']['quests'] ?? [];
        self::assertIsArray($quests);

        $rewardTypes = [];
        foreach ($quests as $row) {
            if (($row['isCompleted'] ?? false) === true && ($row['isRewardClaimed'] ?? false) === true) {
                $rewardTypes[] = $row['rewardType'] ?? null;
            }
        }

        self::assertContains('GOLD', $rewardTypes, 'Expected a completed claimed GOLD quest in payload.');
        self::assertContains('EXPERIENCE', $rewardTypes, 'Expected a completed claimed EXPERIENCE quest in payload.');
        self::assertContains('diamonds', $rewardTypes, 'Expected a completed claimed diamonds quest in payload.');
        self::assertContains('ITEM', $rewardTypes, 'Expected a completed claimed ITEM quest in payload.');
    }

    public function testGetUserQuestsCountsCompletedUnclaimedAsReadyToClaim(): void
    {
        $user = $this->makeUserWithStorage();
        $em = $this->entityManager();

        $tpl = $this->makeInactiveTemplate($em, QuestCategory::GOLD_SPENT, QuestRewardType::GOLD, 50, 301);
        $uq = new UserQuest();
        $uq->setUser($user);
        $uq->setQuestTemplate($tpl);
        $uq->setCurrentProgress($tpl->getTargetValue());
        $uq->setIsRewardClaimed(false);
        $em->persist($uq);
        $em->flush();

        $client = $this->createAuthenticatedClient($user);
        $client->request('GET', '/api/user_quests');

        self::assertResponseIsSuccessful();
        $decoded = $this->assertJsonEnvelopeSuccess($client->getResponse());
        self::assertTrue($decoded['data']['hasUnclaimedRewards'] ?? false);
        self::assertGreaterThanOrEqual(1, $decoded['data']['unclaimedCount'] ?? 0);
    }

    public function testClaimGoldQuestIncreasesGoldAndMarksClaimed(): void
    {
        $user = $this->makeUserWithStorage();
        $em = $this->entityManager();
        $goldBefore = $user->getGold();

        $tpl = $this->makeInactiveTemplate($em, QuestCategory::GOLD_SPENT, QuestRewardType::GOLD, 77, 201);
        $uq = new UserQuest();
        $uq->setUser($user);
        $uq->setQuestTemplate($tpl);
        $uq->setCurrentProgress($tpl->getTargetValue());
        $uq->setIsRewardClaimed(false);
        $em->persist($uq);
        $em->flush();

        $client = $this->createAuthenticatedClient($user);
        $client->request('POST', '/api/user_quests/'.$uq->getId().'/claim-reward');

        self::assertResponseIsSuccessful();
        $decoded = $this->assertJsonEnvelopeSuccess($client->getResponse());
        self::assertSame('questRewardClaimed', $decoded['data']['message'] ?? null);
        self::assertSame('GOLD', $decoded['data']['rewardType'] ?? null);
        self::assertSame(77, $decoded['data']['rewardAmount'] ?? null);
        self::assertSame($goldBefore + 77, $decoded['data']['updatedUser']['gold'] ?? null);

        $em->clear();
        $reloaded = $em->find(UserQuest::class, $uq->getId());
        self::assertNotNull($reloaded);
        self::assertTrue($reloaded->isRewardClaimed());
    }

    public function testClaimItemQuestReturnsRewardItemWithStatistics(): void
    {
        $user = $this->makeUserWithStorage();
        $em = $this->entityManager();

        $tpl = $this->makeInactiveTemplate($em, QuestCategory::FIGHTS_LOST, QuestRewardType::ITEM, 1, 402);
        $uq = new UserQuest();
        $uq->setUser($user);
        $uq->setQuestTemplate($tpl);
        $uq->setCurrentProgress($tpl->getTargetValue());
        $uq->setIsRewardClaimed(false);
        $em->persist($uq);
        $em->flush();

        $client = $this->createAuthenticatedClient($user);
        $client->request('POST', '/api/user_quests/'.$uq->getId().'/claim-reward');

        self::assertResponseIsSuccessful();
        $decoded = $this->assertJsonEnvelopeSuccess($client->getResponse());
        self::assertSame('ITEM', $decoded['data']['rewardType'] ?? null);
        $rewardItem = $decoded['data']['rewardItem'] ?? null;
        self::assertIsArray($rewardItem);
        self::assertArrayHasKey('name', $rewardItem);
        self::assertNotEmpty($rewardItem['name']);
        self::assertArrayHasKey('type', $rewardItem);
        self::assertNotNull($rewardItem['type']);
        self::assertArrayHasKey('statistics', $rewardItem);
        self::assertIsArray($rewardItem['statistics']);
        self::assertArrayHasKey('strongPoints', $rewardItem['statistics']);
        self::assertArrayHasKey('healthPoints', $rewardItem['statistics']);

        $storage = $decoded['data']['updatedUser']['storage'] ?? null;
        self::assertIsArray($storage);
        $claimedId = $rewardItem['id'] ?? null;
        self::assertNotNull($claimedId);
        $chestItem = null;
        foreach ($storage['slots'] ?? [] as $slot) {
            $it = $slot['item'] ?? null;
            if (\is_array($it) && (string) ($it['id'] ?? '') === (string) $claimedId) {
                $chestItem = $it;
                break;
            }
        }
        self::assertIsArray($chestItem, 'Claimed item should appear in updatedUser.storage with same id as rewardItem.');
        self::assertArrayHasKey('statistics', $chestItem);
        self::assertIsArray($chestItem['statistics']);
        self::assertArrayHasKey('strongPoints', $chestItem['statistics']);
    }

    private function makeUserWithStorage(): User
    {
        $user = $this->makePersistedActivatedUser();
        $storageService = static::getContainer()->get(StorageService::class);
        $storage = $storageService->createEmptyStorageForUser($user);
        $user->setStorage($storage);
        $this->entityManager()->flush();

        return $user;
    }

    private function makeInactiveTemplate(
        EntityManagerInterface $em,
        QuestCategory $category,
        QuestRewardType $rewardType,
        int $rewardAmount,
        int $order,
    ): QuestTemplate {
        $t = new QuestTemplate();
        $t->setTitle('FT quest '.$order);
        $t->setDescription('Functional test inactive template.');
        $t->setCategory($category);
        $t->setTargetValue(1);
        $t->setRewardType($rewardType);
        $t->setRewardAmount($rewardAmount);
        $t->setIsActive(false);
        $t->setOrder($order);
        $em->persist($t);
        $em->flush();

        return $t;
    }

    private function persistCompletedClaimedUserQuest(User $user, QuestTemplate $template): void
    {
        $em = $this->entityManager();
        $uq = new UserQuest();
        $uq->setUser($user);
        $uq->setQuestTemplate($template);
        $uq->setCurrentProgress($template->getTargetValue());
        $uq->setIsRewardClaimed(true);
        $uq->setCompletedAt(new \DateTimeImmutable('2025-02-01T10:00:00+00:00'));
        $em->persist($uq);
        $em->flush();
    }
}
