<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Constants;

use App\Domain\Constants\ProgressionConstants;
use PHPUnit\Framework\TestCase;

final class ProgressionConstantsTest extends TestCase
{
    public function testTrainingSkillPointsRewardForLevelSteps(): void
    {
        self::assertSame(2, ProgressionConstants::trainingSkillPointsRewardForLevel(1));
        self::assertSame(2, ProgressionConstants::trainingSkillPointsRewardForLevel(4));
        self::assertSame(3, ProgressionConstants::trainingSkillPointsRewardForLevel(5));
        self::assertSame(3, ProgressionConstants::trainingSkillPointsRewardForLevel(8));
        self::assertSame(4, ProgressionConstants::trainingSkillPointsRewardForLevel(9));
        self::assertSame(2, ProgressionConstants::trainingSkillPointsRewardForLevel(0));
    }
}
