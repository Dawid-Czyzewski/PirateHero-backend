<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Progression;

use App\Entity\QuestTemplate;
use App\Entity\User;
use App\Entity\UserStatistics;
use App\Enum\QuestCategory;
use App\Repository\QuestTemplateRepository;
use App\Repository\UserBestiaryEntryRepository;
use App\Repository\UserDungeonProgressRepository;
use App\Repository\UserQuestRepository;
use App\Repository\UserTitleRepository;
use App\Service\Progression\QuestProgressEvaluator;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class QuestProgressEvaluatorTest extends TestCase
{
    public function testGoldSpentReadsFromUserStatistics(): void
    {
        $stats = (new UserStatistics())->setGoldSpent(750);
        $template = (new QuestTemplate())
            ->setCategory(QuestCategory::GOLD_SPENT)
            ->setTargetValue(1000);

        $evaluator = $this->makeEvaluator();

        self::assertSame(750, $evaluator->getCurrentValueForCategory($stats, $template, $this->createMock(User::class)));
    }

    public function testFightsWonReadsFromUserStatistics(): void
    {
        $stats = (new UserStatistics())->setFightsWon(12);
        $template = (new QuestTemplate())
            ->setCategory(QuestCategory::FIGHTS_WON)
            ->setTargetValue(50);

        $evaluator = $this->makeEvaluator();

        self::assertSame(12, $evaluator->getCurrentValueForCategory($stats, $template, $this->createMock(User::class)));
    }

    private function makeEvaluator(): QuestProgressEvaluator
    {
        $dungeonRepo = $this->createMock(UserDungeonProgressRepository::class);
        $dungeonRepo->method('getProgressMapForUser')->willReturn([]);

        $bestiaryRepo = $this->createMock(UserBestiaryEntryRepository::class);
        $bestiaryRepo->method('countForUser')->willReturn(0);

        $titleRepo = $this->createMock(UserTitleRepository::class);
        $titleRepo->method('getUnlockedMapForUser')->willReturn([]);

        return new QuestProgressEvaluator(
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(UserQuestRepository::class),
            $this->createMock(QuestTemplateRepository::class),
            $dungeonRepo,
            $bestiaryRepo,
            $titleRepo,
        );
    }
}
