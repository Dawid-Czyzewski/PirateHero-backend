<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Economy;

use App\Entity\Coupon;
use App\Entity\UserCoupon;
use App\Enum\CouponType;
use App\Exception\BusinessRuleException;
use App\Exception\ResourceNotFoundException;
use App\Repository\CouponRepository;
use App\Repository\UserCouponRepository;
use App\Service\Economy\CouponService;
use App\Service\Economy\WearableRewardFactory;
use App\Tests\Support\TransactionalEntityManagerMockTrait;
use App\Tests\Support\UnconstructedInstance;
use App\Tests\TestDoubles\UserStubFactory;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class CouponServiceTest extends TestCase
{
    use TransactionalEntityManagerMockTrait;

    public function testRedeemThrowsWhenCodeEmpty(): void
    {
        $connection = $this->createMock(Connection::class);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getConnection')->willReturn($connection);

        $service = new CouponService(
            $em,
            $this->createMock(CouponRepository::class),
            $this->createMock(UserCouponRepository::class),
            UnconstructedInstance::of(WearableRewardFactory::class),
        );

        $this->expectException(BusinessRuleException::class);
        $this->expectExceptionMessage('couponCodeRequired');
        $service->redeemCoupon(UserStubFactory::create(['prefix' => 'cp', 'levelName' => '2']), '   ');
    }

    public function testRedeemThrowsWhenCouponUnknown(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())->method('beginTransaction');
        $connection->expects(self::once())->method('rollBack');

        $user = UserStubFactory::create(['prefix' => 'cp', 'levelName' => '2']);
        $em = $this->mockTransactionalEmForUser($user, ['withConnection' => false]);
        $em->method('getConnection')->willReturn($connection);

        $couponRepository = $this->createMock(CouponRepository::class);
        $couponRepository->method('findByCodeForUpdate')->with('MISSING')->willReturn(null);

        $service = new CouponService(
            $em,
            $couponRepository,
            $this->createMock(UserCouponRepository::class),
            UnconstructedInstance::of(WearableRewardFactory::class),
        );

        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage('couponCodeNotFound');
        $service->redeemCoupon($user, 'MISSING');
    }

    public function testRedeemThrowsWhenCouponExpired(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())->method('beginTransaction');
        $connection->expects(self::once())->method('rollBack');

        $user = UserStubFactory::create(['prefix' => 'cp', 'levelName' => '2']);
        $em = $this->mockTransactionalEmForUser($user, ['withConnection' => false]);
        $em->method('getConnection')->willReturn($connection);

        $coupon = $this->createMock(Coupon::class);
        $coupon->method('isExpired')->willReturn(true);

        $couponRepository = $this->createMock(CouponRepository::class);
        $couponRepository->method('findByCodeForUpdate')->willReturn($coupon);

        $service = new CouponService(
            $em,
            $couponRepository,
            $this->createMock(UserCouponRepository::class),
            UnconstructedInstance::of(WearableRewardFactory::class),
        );

        $this->expectException(BusinessRuleException::class);
        $this->expectExceptionMessage('couponExpired');
        $service->redeemCoupon($user, 'EXPIRED');
    }

    public function testRedeemThrowsWhenMultiUseAlreadyRedeemedBySameUser(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())->method('beginTransaction');
        $connection->expects(self::once())->method('rollBack');

        $user = UserStubFactory::create(['prefix' => 'cp', 'levelName' => '2']);
        $em = $this->mockTransactionalEmForUser($user, ['withConnection' => false]);
        $em->method('getConnection')->willReturn($connection);

        $coupon = $this->createMock(Coupon::class);
        $coupon->method('isExpired')->willReturn(false);
        $coupon->method('getType')->willReturn(CouponType::MULTI_USE);

        $couponRepository = $this->createMock(CouponRepository::class);
        $couponRepository->method('findByCodeForUpdate')->willReturn($coupon);

        $userCouponRepository = $this->createMock(UserCouponRepository::class);
        $userCouponRepository->method('findByUserAndCoupon')->willReturn($this->createMock(UserCoupon::class));

        $service = new CouponService(
            $em,
            $couponRepository,
            $userCouponRepository,
            UnconstructedInstance::of(WearableRewardFactory::class),
        );

        $this->expectException(BusinessRuleException::class);
        $this->expectExceptionMessage('couponAlreadyUsedByYou');
        $service->redeemCoupon($user, 'USED');
    }
}
