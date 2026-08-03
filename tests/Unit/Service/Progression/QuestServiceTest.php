<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Progression;

use App\Entity\Level;
use App\Entity\User;
use App\Entity\UserQuest;
use App\Enum\QuestCategory;
use App\Enum\QuestRewardType;
use App\Exception\BusinessRuleException;
use App\Repository\UserQuestRepository;
use App\Service\Economy\WearableRewardFactory;
use App\Service\Progression\QuestProgressService;
use App\Service\Progression\QuestRewardClaimer;
use App\Service\Progression\QuestService;
use App\Tests\Support\UnconstructedInstance;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class QuestServiceTest extends TestCase
{
    public function testFormatQuestsResponseReturnsEmptyShapeWhenNoQuests(): void
    {
        $repo = $this->createMock(UserQuestRepository::class);
        $repo->method('findByUser')->willReturn([]);

        $service = $this->makeQuestService(
            $this->createMock(EntityManagerInterface::class),
            $repo,
        );

        $out = $service->formatQuestsResponse($this->makeUser());
        self::assertSame([], $out['quests']);
        self::assertFalse($out['hasUnclaimedRewards']);
        self::assertSame(0, $out['unclaimedCount']);
    }

    public function testFormatQuestsResponseCountsUnclaimedCompletedQuests(): void
    {
        $template = $this->createMock(\App\Entity\QuestTemplate::class);
        $template->method('getId')->willReturn(1);
        $template->method('getTitle')->willReturn('Quest title');
        $template->method('getDescription')->willReturn('Quest description');
        $template->method('getCategory')->willReturn(QuestCategory::GOLD_SPENT);
        $template->method('getTargetValue')->willReturn(10);
        $template->method('getRewardType')->willReturn(QuestRewardType::GOLD);
        $template->method('getRewardAmount')->willReturn(5);
        $template->method('getRewardItem')->willReturn(null);

        $userQuest = $this->createMock(UserQuest::class);
        $userQuest->method('getId')->willReturn(1);
        $userQuest->method('getQuestTemplate')->willReturn($template);
        $userQuest->method('getCurrentProgress')->willReturn(10);
        $userQuest->method('getProgressPercentage')->willReturn(100.0);
        $userQuest->method('isCompleted')->willReturn(true);
        $userQuest->method('isRewardClaimed')->willReturn(false);
        $userQuest->method('getCompletedAt')->willReturn(new \DateTimeImmutable('2025-01-01T00:00:00+00:00'));

        $repo = $this->createMock(UserQuestRepository::class);
        $repo->method('findByUser')->willReturn([$userQuest]);

        $service = $this->makeQuestService(
            $this->createMock(EntityManagerInterface::class),
            $repo,
        );

        $out = $service->formatQuestsResponse($this->makeUser());
        self::assertCount(1, $out['quests']);
        self::assertTrue($out['hasUnclaimedRewards']);
        self::assertSame(1, $out['unclaimedCount']);
        self::assertSame('GOLD', $out['quests'][0]['rewardType']);
    }

    public function testFormatQuestsResponsePutsReadyToClaimQuestsFirst(): void
    {
        $readyTemplate = $this->createMock(\App\Entity\QuestTemplate::class);
        $readyTemplate->method('getId')->willReturn(1);
        $readyTemplate->method('getTitle')->willReturn('Ready quest');
        $readyTemplate->method('getDescription')->willReturn('Ready');
        $readyTemplate->method('getCategory')->willReturn(QuestCategory::GOLD_SPENT);
        $readyTemplate->method('getTargetValue')->willReturn(10);
        $readyTemplate->method('getRewardType')->willReturn(QuestRewardType::GOLD);
        $readyTemplate->method('getRewardAmount')->willReturn(5);
        $readyTemplate->method('getRewardItem')->willReturn(null);

        $activeTemplate = $this->createMock(\App\Entity\QuestTemplate::class);
        $activeTemplate->method('getId')->willReturn(2);
        $activeTemplate->method('getTitle')->willReturn('Active quest');
        $activeTemplate->method('getDescription')->willReturn('Active');
        $activeTemplate->method('getCategory')->willReturn(QuestCategory::FIGHTS_WON);
        $activeTemplate->method('getTargetValue')->willReturn(5);
        $activeTemplate->method('getRewardType')->willReturn(QuestRewardType::GOLD);
        $activeTemplate->method('getRewardAmount')->willReturn(3);
        $activeTemplate->method('getRewardItem')->willReturn(null);

        $readyQuest = $this->createMock(UserQuest::class);
        $readyQuest->method('getId')->willReturn(2);
        $readyQuest->method('getQuestTemplate')->willReturn($readyTemplate);
        $readyQuest->method('getCurrentProgress')->willReturn(10);
        $readyQuest->method('getProgressPercentage')->willReturn(100.0);
        $readyQuest->method('isCompleted')->willReturn(true);
        $readyQuest->method('isRewardClaimed')->willReturn(false);
        $readyQuest->method('getCompletedAt')->willReturn(new \DateTimeImmutable('2025-01-02T00:00:00+00:00'));

        $activeQuest = $this->createMock(UserQuest::class);
        $activeQuest->method('getId')->willReturn(1);
        $activeQuest->method('getQuestTemplate')->willReturn($activeTemplate);
        $activeQuest->method('getCurrentProgress')->willReturn(2);
        $activeQuest->method('getProgressPercentage')->willReturn(40.0);
        $activeQuest->method('isCompleted')->willReturn(false);
        $activeQuest->method('isRewardClaimed')->willReturn(false);
        $activeQuest->method('getCompletedAt')->willReturn(null);

        $repo = $this->createMock(UserQuestRepository::class);
        $repo->method('findByUser')->willReturn([$activeQuest, $readyQuest]);

        $service = $this->makeQuestService(
            $this->createMock(EntityManagerInterface::class),
            $repo,
        );

        $out = $service->formatQuestsResponse($this->makeUser());
        self::assertSame('Ready quest', $out['quests'][0]['title']);
        self::assertSame('Active quest', $out['quests'][1]['title']);
    }

    public function testClaimRewardThrowsWhenAlreadyClaimed(): void
    {
        $user = $this->makeUser();
        $userQuest = $this->createMock(UserQuest::class);
        $userQuest->method('getUser')->willReturn($user);
        $userQuest->method('isCompleted')->willReturn(true);
        $userQuest->method('isRewardClaimed')->willReturn(true);

        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())->method('beginTransaction');
        $connection->expects(self::once())->method('rollBack');

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getConnection')->willReturn($connection);
        $em->method('find')->willReturnCallback(
            static function (string $class, mixed $id, ?int $lockMode = null) use ($userQuest, $user) {
                if ($class === UserQuest::class && $lockMode === LockMode::PESSIMISTIC_WRITE) {
                    return $userQuest;
                }
                if ($class === User::class) {
                    return $user;
                }

                return null;
            }
        );

        $service = $this->makeQuestService($em, $this->createMock(UserQuestRepository::class));

        $this->expectException(BusinessRuleException::class);
        $this->expectExceptionMessage('questRewardAlreadyClaimed');

        $service->claimReward($user, 1);
    }

    private function makeQuestService(
        EntityManagerInterface $em,
        UserQuestRepository $repo,
    ): QuestService {
        return new QuestService(
            $em,
            $repo,
            UnconstructedInstance::of(QuestProgressService::class),
            UnconstructedInstance::of(QuestRewardClaimer::class),
            UnconstructedInstance::of(WearableRewardFactory::class),
            $this->createMock(LoggerInterface::class),
        );
    }

    private function makeUser(): User
    {
        $level = (new Level())->setName('1')->setExpToNextLevel(220);

        return (new User())
            ->setEmail(sprintf('quest_%s@test.local', bin2hex(random_bytes(4))))
            ->setUsername(sprintf('u_%s', bin2hex(random_bytes(3))))
            ->setPassword('hash')
            ->setLevel($level)
            ->setGold(100)
            ->setdiamonds(10)
            ->setEnergyPoints(100)
            ->setTrainingPoints(10)
            ->setDuelPoints(10)
            ->setFamePoints(10);
    }
}
