<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Domain\Constants\WeeklyContractConstants;
use App\Entity\PlayerTitle;
use App\Entity\User;
use App\Entity\UserWeeklyContract;
use App\Enum\TitleUnlockType;
use App\Service\Progression\TitleCodes;
use App\Tests\Functional\ApiWebTestCase;

final class WeeklyContractEndpointsTest extends ApiWebTestCase
{
    public function testGetStatusCreatesContractEnvelope(): void
    {
        $user = $this->makePersistedActivatedUser();
        $client = $this->createAuthenticatedClient($user);
        $client->request('GET', '/api/users/weekly-contracts/status');

        self::assertResponseIsSuccessful();
        $data = $this->assertJsonEnvelopeData($client->getResponse());
        self::assertArrayHasKey('type', $data);
        self::assertArrayHasKey('targetValue', $data);
        self::assertSame(0, $data['progress']);
        self::assertFalse($data['canClaim']);
        self::assertSame(TitleCodes::WEEKLY_CORSAIR, $data['titleRewardCode']);
    }

    public function testClaimWithoutCompletionReturnsBusinessError(): void
    {
        $user = $this->makePersistedActivatedUser();
        $client = $this->createAuthenticatedClient($user);
        $client->request('GET', '/api/users/weekly-contracts/status');
        self::assertResponseIsSuccessful();

        $client->request('POST', '/api/users/weekly-contracts/claim');
        self::assertSame(400, $client->getResponse()->getStatusCode());
        $problem = $this->assertProblemJson($client->getResponse());
        self::assertSame('weeklyContractNotComplete', $problem['detail']);
    }

    public function testClaimCompleteContractGrantsRewardsAndMarksClaimed(): void
    {
        $this->ensureWeeklyCorsairTitle();
        $user = $this->makePersistedActivatedUser();
        $em = $this->entityManager();
        $goldBefore = $user->getGold();
        $diamondsBefore = (int) ($user->getDiamonds() ?? 0);
        $level = (int) ($user->getLevel()?->getName() ?? '1');
        $expectedReward = WeeklyContractConstants::claimReward($level);

        $client = $this->createAuthenticatedClient($user);
        $client->request('GET', '/api/users/weekly-contracts/status');
        self::assertResponseIsSuccessful();

        $contract = $em->getRepository(UserWeeklyContract::class)->findOneBy(['user' => $user]);
        self::assertInstanceOf(UserWeeklyContract::class, $contract);
        $contract->setProgress($contract->getTargetValue());
        $em->persist($contract);
        $em->flush();

        $client->request('POST', '/api/users/weekly-contracts/claim');
        self::assertResponseIsSuccessful();
        $decoded = $this->assertJsonEnvelopeSuccess($client->getResponse());
        self::assertSame('weeklyContractClaimed', $decoded['meta']['message'] ?? null);

        $data = $decoded['data'];
        self::assertSame($expectedReward['gold'], $data['rewards']['gold'] ?? null);
        self::assertSame($expectedReward['diamonds'], $data['rewards']['diamonds'] ?? null);
        self::assertTrue($data['titleGranted'] ?? false);
        self::assertSame(TitleCodes::WEEKLY_CORSAIR, $data['titleCode'] ?? null);
        self::assertSame($goldBefore + $expectedReward['gold'], $data['updatedUser']['gold'] ?? null);
        self::assertSame($diamondsBefore + $expectedReward['diamonds'], $data['updatedUser']['diamonds'] ?? null);
        self::assertTrue($data['status']['rewardClaimed'] ?? false);
        self::assertFalse($data['status']['canClaim'] ?? true);

        $em->clear();
        $reloaded = $em->find(UserWeeklyContract::class, $contract->getId());
        self::assertNotNull($reloaded);
        self::assertTrue($reloaded->isRewardClaimed());
        self::assertSame($goldBefore + $expectedReward['gold'], $em->find(User::class, $user->getId())?->getGold());
    }

    private function ensureWeeklyCorsairTitle(): void
    {
        $em = $this->entityManager();
        $repo = $em->getRepository(PlayerTitle::class);
        if ($repo->findOneBy(['code' => TitleCodes::WEEKLY_CORSAIR]) !== null) {
            return;
        }

        $title = new PlayerTitle();
        $title->setCode(TitleCodes::WEEKLY_CORSAIR);
        $title->setNameKey('titles.weekly_corsair.name');
        $title->setDescriptionKey('titles.weekly_corsair.unlockHint');
        $title->setUnlockType(TitleUnlockType::MANUAL);
        $title->setSortOrder(200);
        $em->persist($title);
        $em->flush();
    }
}
