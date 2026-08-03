<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\BoosterTemplate;
use App\Entity\Coupon;
use App\Entity\User;
use App\Enum\BoosterType;
use App\Enum\CouponRewardType;
use App\Enum\CouponType;
use App\Service\Economy\StorageService;
use App\Tests\Functional\ApiWebTestCase;

final class CouponEndpointsFunctionalTest extends ApiWebTestCase
{
    private function persistCoupon(Coupon $coupon): Coupon
    {
        $em = $this->entityManager();
        $em->persist($coupon);
        $em->flush();

        return $coupon;
    }

    private function attachEmptyStorage(User $user): void
    {
        $storage = static::getContainer()->get(StorageService::class)->createEmptyStorageForUser($user);
        $user->setStorage($storage);
        $em = $this->entityManager();
        $em->persist($user);
        $em->flush();
    }

    public function testRedeemMissingCodeReturns400(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'POST',
            '/api/coupons/redeem',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([], \JSON_THROW_ON_ERROR),
        );
        self::assertSame(400, $client->getResponse()->getStatusCode());
        $problem = $this->assertProblemJson($client->getResponse());
        self::assertSame('couponCodeRequired', $problem['detail']);
    }

    public function testRedeemUnknownCodeReturns404(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'POST',
            '/api/coupons/redeem',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['code' => 'NO_SUCH_COUPON_'.bin2hex(random_bytes(4))], \JSON_THROW_ON_ERROR),
        );
        self::assertSame(404, $client->getResponse()->getStatusCode());
        $problem = $this->assertProblemJson($client->getResponse());
        self::assertSame('couponCodeNotFound', $problem['detail']);
    }

    public function testRedeemGoldMultiUseIncreasesBalance(): void
    {
        $code = 'FN_TEST_GOLD_'.strtoupper(bin2hex(random_bytes(4)));
        $amount = 77;
        $coupon = new Coupon();
        $coupon->setCode($code);
        $coupon->setType(CouponType::MULTI_USE);
        $coupon->setRewardType(CouponRewardType::GOLD);
        $coupon->setRewardValue($amount);
        $this->persistCoupon($coupon);

        $user = $this->makePersistedActivatedUser();
        $goldBefore = (int) $user->getGold();
        $client = $this->createAuthenticatedClient($user);
        $client->request(
            'POST',
            '/api/coupons/redeem',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['code' => $code], \JSON_THROW_ON_ERROR),
        );
        self::assertSame(200, $client->getResponse()->getStatusCode(), $client->getResponse()->getContent());
        $wrap = $this->assertJsonEnvelopeSuccess($client->getResponse());
        self::assertSame('couponRedeemed', $wrap['meta']['message'] ?? null);
        self::assertTrue($wrap['data']['success']);
        self::assertSame('GOLD', $wrap['data']['reward']['type']);
        self::assertSame($amount, $wrap['data']['reward']['amount']);
        self::assertSame($code, $wrap['data']['coupon']['code']);
        self::assertIsArray($wrap['data']['history'] ?? null);
        self::assertCount(1, $wrap['data']['history']);
        self::assertSame($code, $wrap['data']['history'][0]['code'] ?? null);

        $this->entityManager()->refresh($user);
        self::assertSame($goldBefore + $amount, (int) $user->getGold());
    }

    public function testRedeemMultiUseSecondTimeReturns400(): void
    {
        $code = 'FN_TEST_TWICE_'.strtoupper(bin2hex(random_bytes(4)));
        $coupon = new Coupon();
        $coupon->setCode($code);
        $coupon->setType(CouponType::MULTI_USE);
        $coupon->setRewardType(CouponRewardType::GOLD);
        $coupon->setRewardValue(10);
        $this->persistCoupon($coupon);

        $user = $this->makePersistedActivatedUser();
        $client = $this->createAuthenticatedClient($user);

        $client->request(
            'POST',
            '/api/coupons/redeem',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['code' => $code], \JSON_THROW_ON_ERROR),
        );
        self::assertSame(200, $client->getResponse()->getStatusCode());

        $client->request(
            'POST',
            '/api/coupons/redeem',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['code' => $code], \JSON_THROW_ON_ERROR),
        );
        self::assertSame(400, $client->getResponse()->getStatusCode());
        $problem = $this->assertProblemJson($client->getResponse());
        self::assertSame('couponAlreadyUsedByYou', $problem['detail']);
    }

    public function testRedeemDiamondsMultiUse(): void
    {
        $code = 'FN_TEST_DIAM_'.strtoupper(bin2hex(random_bytes(4)));
        $amount = 3;
        $coupon = new Coupon();
        $coupon->setCode($code);
        $coupon->setType(CouponType::MULTI_USE);
        $coupon->setRewardType(CouponRewardType::diamonds);
        $coupon->setRewardValue($amount);
        $this->persistCoupon($coupon);

        $user = $this->makePersistedActivatedUser();
        $before = (int) $user->getDiamonds();
        $client = $this->createAuthenticatedClient($user);
        $client->request(
            'POST',
            '/api/coupons/redeem',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['code' => $code], \JSON_THROW_ON_ERROR),
        );
        self::assertSame(200, $client->getResponse()->getStatusCode());
        $wrap = $this->assertJsonEnvelopeSuccess($client->getResponse());
        self::assertSame('diamonds', $wrap['data']['reward']['type']);
        self::assertSame($amount, $wrap['data']['reward']['amount']);

        $this->entityManager()->refresh($user);
        self::assertSame($before + $amount, (int) $user->getDiamonds());
    }

    public function testHistoryListsRedemption(): void
    {
        $code = 'FN_TEST_HIST_'.strtoupper(bin2hex(random_bytes(4)));
        $coupon = new Coupon();
        $coupon->setCode($code);
        $coupon->setType(CouponType::MULTI_USE);
        $coupon->setRewardType(CouponRewardType::GOLD);
        $coupon->setRewardValue(5);
        $this->persistCoupon($coupon);

        $user = $this->makePersistedActivatedUser();
        $client = $this->createAuthenticatedClient($user);
        $client->request(
            'POST',
            '/api/coupons/redeem',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['code' => $code], \JSON_THROW_ON_ERROR),
        );
        self::assertSame(200, $client->getResponse()->getStatusCode());

        $client->request('GET', '/api/coupons/history');
        self::assertSame(200, $client->getResponse()->getStatusCode());
        $wrap = $this->assertJsonEnvelopeSuccess($client->getResponse());
        self::assertArrayHasKey('history', $wrap['data']);
        $codes = array_map(static fn (array $row) => $row['code'], $wrap['data']['history']);
        self::assertContains($code, $codes);
    }

    public function testRedeemBoosterGrantsUserBooster(): void
    {
        $em = $this->entityManager();
        $tpl = new BoosterTemplate();
        $tpl->setName('fn_coupon_booster');
        $tpl->setType(BoosterType::ENERGY);
        $tpl->setEffectAmount(5);
        $tpl->setTier(1);
        $em->persist($tpl);
        $em->flush();

        $code = 'FN_TEST_BOOST_'.strtoupper(bin2hex(random_bytes(4)));
        $coupon = new Coupon();
        $coupon->setCode($code);
        $coupon->setType(CouponType::MULTI_USE);
        $coupon->setRewardType(CouponRewardType::BOOSTER);
        $coupon->setBoosterTemplate($tpl);
        $coupon->setBoosterDurationDays(2);
        $this->persistCoupon($coupon);

        $user = $this->makePersistedActivatedUser();
        $client = $this->createAuthenticatedClient($user);
        $client->request(
            'POST',
            '/api/coupons/redeem',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['code' => $code], \JSON_THROW_ON_ERROR),
        );
        self::assertSame(200, $client->getResponse()->getStatusCode(), $client->getResponse()->getContent());
        $wrap = $this->assertJsonEnvelopeSuccess($client->getResponse());
        self::assertSame('BOOSTER', $wrap['data']['reward']['type']);
        self::assertSame((int) $tpl->getId(), $wrap['data']['reward']['boosterTemplateId']);
        self::assertSame(2, $wrap['data']['reward']['durationDays']);
    }

    public function testRedeemItemWithStorage(): void
    {
        $code = 'FN_TEST_ITEM_'.strtoupper(bin2hex(random_bytes(4)));
        $coupon = new Coupon();
        $coupon->setCode($code);
        $coupon->setType(CouponType::MULTI_USE);
        $coupon->setRewardType(CouponRewardType::ITEM);
        $coupon->setRewardData([
            'rarity' => 'RARE',
            'name' => 'Fn Coupon Item',
            'type' => 'weapon',
        ]);
        $this->persistCoupon($coupon);

        $user = $this->makePersistedActivatedUser();
        $this->attachEmptyStorage($user);

        $client = $this->createAuthenticatedClient($user);
        $client->request(
            'POST',
            '/api/coupons/redeem',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['code' => $code], \JSON_THROW_ON_ERROR),
        );
        self::assertSame(200, $client->getResponse()->getStatusCode(), $client->getResponse()->getContent());
        $wrap = $this->assertJsonEnvelopeSuccess($client->getResponse());
        self::assertSame('ITEM', $wrap['data']['reward']['type']);
        self::assertSame('Fn Coupon Item', $wrap['data']['reward']['itemName']);
    }
}
