<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Progression;

use App\Entity\Level;
use App\Entity\User;
use App\Entity\UserStatistics;
use App\Enum\QuestCategory;
use App\Enum\WearableItemRarity;
use App\Repository\QuestTemplateRepository;
use App\Repository\UserBestiaryEntryRepository;
use App\Repository\UserDungeonProgressRepository;
use App\Repository\UserQuestRepository;
use App\Repository\UserStatisticsRepository;
use App\Repository\UserTitleRepository;
use App\Service\Progression\DefaultQuestTemplatesProvisioner;
use App\Service\Progression\QuestProgressEvaluator;
use App\Service\Progression\QuestProgressService;
use App\Service\Progression\TitleService;
use App\Service\Progression\UserQuestInitializer;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;

final class QuestProgressServiceTest extends TestCase
{
    public function testCheckAndUpdateProgressCreatesStatisticsAndFlushes(): void
    {
        $user = $this->makeUser();

        $qb = $this->createMock(QueryBuilder::class);
        foreach (['where', 'andWhere', 'setParameter'] as $m) {
            $qb->method($m)->willReturnSelf();
        }
        $query = $this->createMock(Query::class);
        $query->method('getResult')->willReturn([]);
        $qb->method('getQuery')->willReturn($query);

        $questTemplateRepo = $this->createMock(QuestTemplateRepository::class);
        $questTemplateRepo->method('createQueryBuilder')->willReturn($qb);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('persist')->with(self::callback(static function ($e) use ($user): bool {
            return $e === $user->getUserStatistics();
        }));
        $em->expects(self::exactly(2))->method('flush');

        $titleService = $this->createMock(TitleService::class);
        $titleService->expects(self::once())->method('syncUnlocks')->with($user);

        $service = $this->makeService(
            $em,
            $this->createMock(UserQuestRepository::class),
            $questTemplateRepo,
            $this->createMock(UserStatisticsRepository::class),
            $this->createMock(DefaultQuestTemplatesProvisioner::class),
            $titleService,
        );

        $service->checkAndUpdateProgress($user, QuestCategory::FIGHTS_WON, 1);

        self::assertNotNull($user->getUserStatistics());
        self::assertSame(1, $user->getUserStatistics()->getFightsWon());
    }

    public function testRecordItemCollectedIncrementsLegendaryCounter(): void
    {
        $user = $this->makeUser();
        $stats = new UserStatistics();
        $stats->setUser($user);
        $user->setUserStatistics($stats);

        $qb = $this->createMock(QueryBuilder::class);
        foreach (['where', 'andWhere', 'setParameter'] as $m) {
            $qb->method($m)->willReturnSelf();
        }
        $query = $this->createMock(Query::class);
        $query->method('getResult')->willReturn([]);
        $qb->method('getQuery')->willReturn($query);

        $questTemplateRepo = $this->createMock(QuestTemplateRepository::class);
        $questTemplateRepo->method('createQueryBuilder')->willReturn($qb);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('flush');

        $titleService = $this->createMock(TitleService::class);
        $titleService->expects(self::once())->method('syncUnlocks')->with($user);

        $service = $this->makeService(
            $em,
            $this->createMock(UserQuestRepository::class),
            $questTemplateRepo,
            $this->createMock(UserStatisticsRepository::class),
            $this->createMock(DefaultQuestTemplatesProvisioner::class),
            $titleService,
        );

        $service->recordItemCollected($user, WearableItemRarity::LEGENDARY);

        self::assertSame(1, $user->getUserStatistics()->getLegendaryItemsCollected());
        self::assertSame(1, $user->getUserStatistics()->getItemsCollected());
        self::assertSame(1, $user->getUserStatistics()->getRareItemsCollected());
    }

    public function testRecordItemCollectedIncrementsEpicCounter(): void
    {
        $user = $this->makeUser();
        $stats = new UserStatistics();
        $stats->setUser($user);
        $user->setUserStatistics($stats);

        $qb = $this->createMock(QueryBuilder::class);
        foreach (['where', 'andWhere', 'setParameter'] as $m) {
            $qb->method($m)->willReturnSelf();
        }
        $query = $this->createMock(Query::class);
        $query->method('getResult')->willReturn([]);
        $qb->method('getQuery')->willReturn($query);

        $questTemplateRepo = $this->createMock(QuestTemplateRepository::class);
        $questTemplateRepo->method('createQueryBuilder')->willReturn($qb);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('flush');

        $titleService = $this->createMock(TitleService::class);
        $titleService->expects(self::once())->method('syncUnlocks')->with($user);

        $service = $this->makeService(
            $em,
            $this->createMock(UserQuestRepository::class),
            $questTemplateRepo,
            $this->createMock(UserStatisticsRepository::class),
            $this->createMock(DefaultQuestTemplatesProvisioner::class),
            $titleService,
        );

        $service->recordItemCollected($user, WearableItemRarity::EPIC);

        self::assertSame(1, $user->getUserStatistics()->getEpicItemsCollected());
        self::assertSame(1, $user->getUserStatistics()->getItemsCollected());
        self::assertSame(1, $user->getUserStatistics()->getRareItemsCollected());
    }

    public function testUpdateUserLevelDoesNotFlushWhenLevelNotIncreased(): void
    {
        $user = $this->makeUser();
        $stats = new UserStatistics();
        $stats->setUser($user);
        $stats->setLevelsReached(10);
        $user->setUserStatistics($stats);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::never())->method('flush');

        $service = $this->makeService(
            $em,
            $this->createMock(UserQuestRepository::class),
            $this->createMock(QuestTemplateRepository::class),
            $this->createMock(UserStatisticsRepository::class),
            $this->createMock(DefaultQuestTemplatesProvisioner::class),
            $this->createMock(TitleService::class),
        );

        $service->updateUserLevel($user, 10);
    }

    public function testInitializeUserQuestsSkipsWhenUserQuestAlreadyExists(): void
    {
        $user = $this->makeUser();
        $stats = new UserStatistics();
        $stats->setUser($user);
        $user->setUserStatistics($stats);

        $template = $this->createMock(\App\Entity\QuestTemplate::class);
        $template->method('getId')->willReturn(7);
        $template->method('getCategory')->willReturn(QuestCategory::FIGHTS_WON);
        $template->method('getTargetValue')->willReturn(99);

        $questRepo = $this->createMock(QuestTemplateRepository::class);
        $questRepo->method('findActiveOrdered')->willReturn([$template]);

        $userQuestRepo = $this->createMock(UserQuestRepository::class);
        $userQuestRepo->method('findByUserAndTemplate')->willReturn($this->createMock(\App\Entity\UserQuest::class));

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('flush');

        $provisioner = $this->createMock(DefaultQuestTemplatesProvisioner::class);
        $provisioner->expects(self::once())->method('ensureActiveTemplatesExist');
        $provisioner->expects(self::once())->method('ensureItemCollectionQuestTemplatesMissing');
        $provisioner->expects(self::once())->method('ensureThursdayQuestTemplatesMissing');
        $provisioner->expects(self::once())->method('ensureFridayQuestTemplatesMissing');
        $provisioner->expects(self::once())->method('ensureMondayQuestTemplatesMissing');
        $provisioner->expects(self::once())->method('ensureTuesdayQuestTemplatesMissing');
        $provisioner->expects(self::once())->method('ensureWednesdayQuestTemplatesMissing');
        $provisioner->expects(self::once())->method('ensureThursdayContentExpansionQuestTemplatesMissing');
        $provisioner->expects(self::once())->method('ensureQuestTemplateCodesBackfilled');
        $provisioner->expects(self::never())->method('ensureStarterTemplatesMissing');
        $provisioner->expects(self::never())->method('ensureTestItemClaimQuestTemplatesMissing');

        $service = $this->makeService(
            $em,
            $userQuestRepo,
            $questRepo,
            $this->createMock(UserStatisticsRepository::class),
            $provisioner,
            $this->createMock(TitleService::class),
        );

        $service->initializeUserQuests($user);
    }

    private function makeService(
        EntityManagerInterface $em,
        UserQuestRepository $userQuestRepository,
        QuestTemplateRepository $questTemplateRepository,
        UserStatisticsRepository $userStatisticsRepository,
        DefaultQuestTemplatesProvisioner $defaultQuestTemplatesProvisioner,
        TitleService $titleService,
    ): QuestProgressService {
        $dungeonRepo = $this->createMock(UserDungeonProgressRepository::class);
        $dungeonRepo->method('getProgressMapForUser')->willReturn([]);

        $bestiaryRepo = $this->createMock(UserBestiaryEntryRepository::class);
        $bestiaryRepo->method('countForUser')->willReturn(0);

        $userTitleRepo = $this->createMock(UserTitleRepository::class);
        $userTitleRepo->method('getUnlockedMapForUser')->willReturn([]);

        $evaluator = new QuestProgressEvaluator(
            $em,
            $userQuestRepository,
            $questTemplateRepository,
            $dungeonRepo,
            $bestiaryRepo,
            $userTitleRepo,
        );

        $initializer = new UserQuestInitializer(
            $em,
            $userQuestRepository,
            $questTemplateRepository,
            $defaultQuestTemplatesProvisioner,
            $evaluator,
        );

        return new QuestProgressService(
            $em,
            $evaluator,
            $initializer,
            $titleService,
            $questTemplateRepository,
        );
    }

    private function makeUser(): User
    {
        $level = (new Level())->setName('2')->setExpToNextLevel(100);

        return (new User())
            ->setEmail(sprintf('qp_%s@test.local', bin2hex(random_bytes(3))))
            ->setUsername(sprintf('u_%s', bin2hex(random_bytes(3))))
            ->setPassword('x')
            ->setLevel($level)
            ->setGold(0)
            ->setdiamonds(0)
            ->setEnergyPoints(100)
            ->setTrainingPoints(10)
            ->setDuelPoints(10)
            ->setFamePoints(0);
    }
}
