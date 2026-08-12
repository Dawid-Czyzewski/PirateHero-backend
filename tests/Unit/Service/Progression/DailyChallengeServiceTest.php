<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Progression;

use App\Domain\Constants\DailyChallengeConstants;
use App\Entity\Level;
use App\Entity\User;
use App\Entity\UserDailyChallenge;
use App\Enum\DailyChallengeType;
use App\Repository\UserDailyChallengeDayRepository;
use App\Repository\UserDailyChallengeRepository;
use App\Service\Progression\DailyChallengeService;
use App\Service\User\LevelService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class DailyChallengeServiceTest extends TestCase
{
    public function testTargetFormulas(): void
    {
        self::assertSame(2, DailyChallengeConstants::targetForType(DailyChallengeType::Missions->value, 10));
        self::assertSame(4, DailyChallengeConstants::targetForType(DailyChallengeType::Missions->value, 50));
        self::assertSame(1, DailyChallengeConstants::targetForType(DailyChallengeType::ArenaWins->value, 10));
        self::assertSame(2, DailyChallengeConstants::targetForType(DailyChallengeType::ArenaWins->value, 20));
        self::assertSame(200, DailyChallengeConstants::targetForType(DailyChallengeType::GoldSpent->value, 10));
        self::assertSame(1000, DailyChallengeConstants::targetForType(DailyChallengeType::GoldSpent->value, 50));
    }

    public function testGetStatusCreatesThreeChallenges(): void
    {
        $user = $this->makeUser(25);
        $challengeRepo = $this->createMock(UserDailyChallengeRepository::class);
        $dayRepo = $this->createMock(UserDailyChallengeDayRepository::class);
        $em = $this->createMock(EntityManagerInterface::class);

        $challengeRepo->method('findForUserDate')->willReturnOnConsecutiveCalls([], $this->builtChallenges($user));
        $dayRepo->method('findOneForUserDate')->willReturn(null);
        $em->expects(self::atLeastOnce())->method('persist');
        $em->expects(self::atLeastOnce())->method('flush');

        $service = new DailyChallengeService(
            $em,
            $challengeRepo,
            $dayRepo,
            $this->createMock(LevelService::class),
        );

        $status = $service->getStatus($user);
        self::assertCount(3, $status['challenges']);
        self::assertSame(DailyChallengeType::Missions->value, $status['challenges'][0]['type']);
        self::assertSame(DailyChallengeType::ArenaWins->value, $status['challenges'][1]['type']);
        self::assertSame(DailyChallengeType::GoldSpent->value, $status['challenges'][2]['type']);
    }

    /**
     * @return list<UserDailyChallenge>
     */
    private function builtChallenges(User $user): array
    {
        $out = [];
        $types = [
            DailyChallengeType::Missions,
            DailyChallengeType::ArenaWins,
            DailyChallengeType::GoldSpent,
        ];
        foreach ($types as $i => $type) {
            $c = new UserDailyChallenge();
            $c->setUser($user);
            $c->setSlot($i + 1);
            $c->setType($type->value);
            $c->setTargetValue(DailyChallengeConstants::targetForType($type->value, 25));
            $c->setProgress(0);
            $out[] = $c;
        }

        return $out;
    }

    private function makeUser(int $level): User
    {
        $lvl = (new Level())->setName((string) $level)->setExpToNextLevel(100);
        $user = new User();
        $user->setEmail('daily@test.local');
        $user->setUsername('dailyhero');
        $user->setPassword('hash');
        $user->setLevel($lvl);
        $user->setGold(1000);

        return $user;
    }
}
