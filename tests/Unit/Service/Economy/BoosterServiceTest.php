<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Economy;

use App\Entity\BoosterTemplate;
use App\Entity\UserAvailableBooster;
use App\Entity\UserBooster;
use App\Entity\UserCapacities;
use App\Enum\BoosterType;
use App\Exception\BusinessRuleException;
use App\Exception\OperationForbiddenException;
use App\Exception\ResourceNotFoundException;
use App\Repository\BoosterTemplateRepository;
use App\Repository\UserAvailableBoosterRepository;
use App\Repository\UserBoosterRepository;
use App\Service\Economy\BoosterService;
use App\Service\Progression\DailyChallengeService;
use App\Tests\Support\TransactionalEntityManagerMockTrait;
use App\Tests\TestDoubles\UserStubFactory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class BoosterServiceTest extends TestCase
{
    use TransactionalEntityManagerMockTrait;

    public function testBuyBoosterThrowsWhenOfferNotFound(): void
    {
        $offerRepo = $this->createMock(UserAvailableBoosterRepository::class);
        $offerRepo->method('find')->with(9)->willReturn(null);

        $service = new BoosterService(
            $this->mockTransactionalEmForUser(UserStubFactory::create(['prefix' => 'a', 'levelName' => '5', 'gold' => 50_000, 'diamonds' => 50_000])),
            $this->createMock(BoosterTemplateRepository::class),
            $offerRepo,
            $this->createMock(UserBoosterRepository::class),
            $this->createMock(LoggerInterface::class),
            $this->createMock(DailyChallengeService::class),
        );

        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage('boosterOfferNotFound');
        $service->buyBooster(UserStubFactory::create(['prefix' => 'a', 'levelName' => '5', 'gold' => 50_000, 'diamonds' => 50_000]), 9);
    }

    public function testBuyBoosterThrowsWhenOfferOwnedBySomeoneElse(): void
    {
        $owner = UserStubFactory::create(['prefix' => 'owner', 'levelName' => '5', 'gold' => 50_000, 'diamonds' => 50_000]);
        $buyer = UserStubFactory::create(['prefix' => 'buyer', 'levelName' => '5', 'gold' => 50_000, 'diamonds' => 50_000]);

        $offer = $this->createMock(UserAvailableBooster::class);
        $offer->method('getUser')->willReturn($owner);

        $offerRepo = $this->createMock(UserAvailableBoosterRepository::class);
        $offerRepo->method('find')->willReturn($offer);

        $service = new BoosterService(
            $this->mockTransactionalEmForUser($buyer, [
                'withPersist' => true,
                'withRemove' => true,
                'withFlush' => true,
            ]),
            $this->createMock(BoosterTemplateRepository::class),
            $offerRepo,
            $this->createMock(UserBoosterRepository::class),
            $this->createMock(LoggerInterface::class),
            $this->createMock(DailyChallengeService::class),
        );

        $this->expectException(OperationForbiddenException::class);
        $this->expectExceptionMessage('boosterOfferNotOwned');
        $service->buyBooster($buyer, 1);
    }

    public function testBuyBoosterThrowsWhenNotEnoughGold(): void
    {
        $user = UserStubFactory::create(['prefix' => 'u', 'levelName' => '5', 'gold' => 10, 'diamonds' => 50_000]);

        $template = $this->createConfiguredMock(BoosterTemplate::class, [
            'getType' => BoosterType::ENERGY,
            'getTier' => 1,
        ]);

        $offer = $this->createMock(UserAvailableBooster::class);
        $offer->method('getUser')->willReturn($user);
        $offer->method('getBoosterTemplate')->willReturn($template);
        $offer->method('getPrice')->willReturn(500);
        $offer->method('isUseGold')->willReturn(true);

        $offerRepo = $this->createMock(UserAvailableBoosterRepository::class);
        $offerRepo->method('find')->willReturn($offer);

        $activeRepo = $this->createMock(UserBoosterRepository::class);
        $activeRepo->method('findActiveBoosterByUserAndType')->willReturn(null);

        $em = $this->mockTransactionalEmForUser($user, [
            'withPersist' => true,
            'withRemove' => true,
            'withFlush' => true,
        ]);
        $em->expects(self::never())->method('flush');

        $service = new BoosterService(
            $em,
            $this->createMock(BoosterTemplateRepository::class),
            $offerRepo,
            $activeRepo,
            $this->createMock(LoggerInterface::class),
            $this->createMock(DailyChallengeService::class),
        );

        $this->expectException(BusinessRuleException::class);
        $this->expectExceptionMessage('notEnoughGold');
        $service->buyBooster($user, 1);
    }

    public function testUseBoosterThrowsWhenMissing(): void
    {
        $repo = $this->createMock(UserBoosterRepository::class);
        $repo->method('find')->with(3)->willReturn(null);

        $service = new BoosterService(
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(BoosterTemplateRepository::class),
            $this->createMock(UserAvailableBoosterRepository::class),
            $repo,
            $this->createMock(LoggerInterface::class),
            $this->createMock(DailyChallengeService::class),
        );

        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage('userBoosterNotFound');
        $service->useBooster(UserStubFactory::create(['prefix' => 'x', 'levelName' => '5', 'gold' => 50_000, 'diamonds' => 50_000]), 3);
    }

    public function testUseBoosterThrowsWhenExpired(): void
    {
        $user = UserStubFactory::create(['prefix' => 'u', 'levelName' => '5', 'gold' => 50_000, 'diamonds' => 50_000]);

        $boost = $this->createMock(UserBooster::class);
        $boost->method('getUser')->willReturn($user);
        $boost->method('isExpired')->willReturn(true);

        $repo = $this->createMock(UserBoosterRepository::class);
        $repo->method('find')->willReturn($boost);

        $service = new BoosterService(
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(BoosterTemplateRepository::class),
            $this->createMock(UserAvailableBoosterRepository::class),
            $repo,
            $this->createMock(LoggerInterface::class),
            $this->createMock(DailyChallengeService::class),
        );

        $this->expectException(BusinessRuleException::class);
        $this->expectExceptionMessage('boosterExpired');
        $service->useBooster($user, 9);
    }

    public function testGetAvailableBoostersDelegatesToRepository(): void
    {
        $user = UserStubFactory::create(['prefix' => 'u', 'levelName' => '5', 'gold' => 50_000, 'diamonds' => 50_000]);
        $expected = [$this->createMock(UserAvailableBooster::class)];

        $offerRepo = $this->createMock(UserAvailableBoosterRepository::class);
        $offerRepo->expects(self::once())->method('findBy')->with(['user' => $user])->willReturn($expected);

        $service = new BoosterService(
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(BoosterTemplateRepository::class),
            $offerRepo,
            $this->createMock(UserBoosterRepository::class),
            $this->createMock(LoggerInterface::class),
            $this->createMock(DailyChallengeService::class),
        );

        self::assertSame($expected, $service->getAvailableBoostersForUser($user));
    }

    public function testCalculateActualCapacitySetsBaseWhenNoActiveBoosters(): void
    {
        $user = UserStubFactory::create(['prefix' => 'u', 'levelName' => '5', 'gold' => 50_000, 'diamonds' => 50_000]);
        $cap = new UserCapacities();
        $cap->setUser($user);
        $cap->setEnergyPoints(5);
        $cap->setTrainingPoints(5);
        $cap->setFightPoints(5);
        $user->setUserCapacities($cap);

        $repo = $this->createMock(UserBoosterRepository::class);
        $repo->method('findActiveBoostersByUser')->willReturn([]);

        $em = $this->createMock(EntityManagerInterface::class);

        $service = new BoosterService(
            $em,
            $this->createMock(BoosterTemplateRepository::class),
            $this->createMock(UserAvailableBoosterRepository::class),
            $repo,
            $this->createMock(LoggerInterface::class),
            $this->createMock(DailyChallengeService::class),
        );

        $service->calculateActualCapacity($user);

        self::assertSame(100, $cap->getEnergyPoints());
        self::assertSame(10, $cap->getTrainingPoints());
        self::assertSame(10, $cap->getFightPoints());
    }

    public function testCleanupExpiredDoesNothingWhenNoExpired(): void
    {
        $user = UserStubFactory::create(['prefix' => 'u', 'levelName' => '5', 'gold' => 50_000, 'diamonds' => 50_000]);

        $repo = $this->createMock(UserBoosterRepository::class);
        $repo->method('findExpiredBoostersByUser')->willReturn([]);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::never())->method('flush');

        $service = new BoosterService(
            $em,
            $this->createMock(BoosterTemplateRepository::class),
            $this->createMock(UserAvailableBoosterRepository::class),
            $repo,
            $this->createMock(LoggerInterface::class),
            $this->createMock(DailyChallengeService::class),
        );

        $service->cleanupExpiredBoostersAndGenerateNew($user);
    }
}
