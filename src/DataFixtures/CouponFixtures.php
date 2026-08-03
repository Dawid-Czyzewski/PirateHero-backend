<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\BoosterTemplate;
use App\Entity\Coupon;
use App\Enum\CouponRewardType;
use App\Enum\CouponType;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class CouponFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    public static function getGroups(): array
    {
        return ['catalog'];
    }

    public function getDependencies(): array
    {
        return [BoosterTemplateFixtures::class];
    }

    public function load(ObjectManager $manager): void
    {
        if ($manager->getRepository(Coupon::class)->count([]) > 0) {
            return;
        }

        $coupon1 = new Coupon();
        $coupon1->setCode('GOLD100');
        $coupon1->setType(CouponType::MULTI_USE);
        $coupon1->setRewardType(CouponRewardType::GOLD);
        $coupon1->setRewardValue(100);
        $manager->persist($coupon1);

        $coupon2 = new Coupon();
        $coupon2->setCode('GOLD500');
        $coupon2->setType(CouponType::MULTI_USE);
        $coupon2->setRewardType(CouponRewardType::GOLD);
        $coupon2->setRewardValue(500);
        $manager->persist($coupon2);

        $coupon3 = new Coupon();
        $coupon3->setCode('GOLD1000');
        $coupon3->setType(CouponType::SINGLE_USE);
        $coupon3->setRewardType(CouponRewardType::GOLD);
        $coupon3->setRewardValue(1000);
        $manager->persist($coupon3);

        $coupon4 = new Coupon();
        $coupon4->setCode('FAME10');
        $coupon4->setType(CouponType::MULTI_USE);
        $coupon4->setRewardType(CouponRewardType::diamonds);
        $coupon4->setRewardValue(10);
        $manager->persist($coupon4);

        $coupon5 = new Coupon();
        $coupon5->setCode('FAME50');
        $coupon5->setType(CouponType::MULTI_USE);
        $coupon5->setRewardType(CouponRewardType::diamonds);
        $coupon5->setRewardValue(50);
        $manager->persist($coupon5);

        $coupon6 = new Coupon();
        $coupon6->setCode('FAME100');
        $coupon6->setType(CouponType::SINGLE_USE);
        $coupon6->setRewardType(CouponRewardType::diamonds);
        $coupon6->setRewardValue(100);
        $manager->persist($coupon6);

        $boosterRepository = $manager->getRepository(BoosterTemplate::class);
        $boosters = $boosterRepository->findBy([], ['id' => 'ASC'], 3);

        if (count($boosters) >= 3) {
            $coupon7 = new Coupon();
            $coupon7->setCode('BOOST1');
            $coupon7->setType(CouponType::MULTI_USE);
            $coupon7->setRewardType(CouponRewardType::BOOSTER);
            $coupon7->setBoosterTemplate($boosters[0]);
            $coupon7->setBoosterDurationDays(7);
            $manager->persist($coupon7);

            $coupon8 = new Coupon();
            $coupon8->setCode('BOOST2');
            $coupon8->setType(CouponType::MULTI_USE);
            $coupon8->setRewardType(CouponRewardType::BOOSTER);
            $coupon8->setBoosterTemplate($boosters[1]);
            $coupon8->setBoosterDurationDays(14);
            $manager->persist($coupon8);

            $coupon9 = new Coupon();
            $coupon9->setCode('BOOST3');
            $coupon9->setType(CouponType::SINGLE_USE);
            $coupon9->setRewardType(CouponRewardType::BOOSTER);
            $coupon9->setBoosterTemplate($boosters[2]);
            $coupon9->setBoosterDurationDays(30);
            $manager->persist($coupon9);
        }

        $coupon10 = new Coupon();
        $coupon10->setCode('ITEM1');
        $coupon10->setType(CouponType::MULTI_USE);
        $coupon10->setRewardType(CouponRewardType::ITEM);
        $coupon10->setRewardData(['rarity' => 'RARE', 'type' => 'weapon', 'name' => 'Reward Item #1']);
        $manager->persist($coupon10);

        $coupon11 = new Coupon();
        $coupon11->setCode('ITEM2');
        $coupon11->setType(CouponType::MULTI_USE);
        $coupon11->setRewardType(CouponRewardType::ITEM);
        $coupon11->setRewardData(['rarity' => 'EPIC', 'type' => 'boots', 'name' => 'Reward Item #2']);
        $manager->persist($coupon11);

        $coupon12 = new Coupon();
        $coupon12->setCode('ITEM3');
        $coupon12->setType(CouponType::SINGLE_USE);
        $coupon12->setRewardType(CouponRewardType::ITEM);
        $coupon12->setRewardData(['rarity' => 'LEGENDARY', 'type' => 'amulet', 'name' => 'Legendary Reward Item']);
        $manager->persist($coupon12);

        $testGold = new Coupon();
        $testGold->setCode('TEST_GOLD');
        $testGold->setType(CouponType::MULTI_USE);
        $testGold->setRewardType(CouponRewardType::GOLD);
        $testGold->setRewardValue(50);
        $manager->persist($testGold);

        $testDiamonds = new Coupon();
        $testDiamonds->setCode('TEST_DIAMONDS');
        $testDiamonds->setType(CouponType::MULTI_USE);
        $testDiamonds->setRewardType(CouponRewardType::diamonds);
        $testDiamonds->setRewardValue(5);
        $manager->persist($testDiamonds);

        if (count($boosters) >= 1) {
            $testBooster = new Coupon();
            $testBooster->setCode('TEST_BOOSTER');
            $testBooster->setType(CouponType::MULTI_USE);
            $testBooster->setRewardType(CouponRewardType::BOOSTER);
            $testBooster->setBoosterTemplate($boosters[0]);
            $testBooster->setBoosterDurationDays(3);
            $manager->persist($testBooster);
        }

        $testItem = new Coupon();
        $testItem->setCode('TEST_ITEM');
        $testItem->setType(CouponType::MULTI_USE);
        $testItem->setRewardType(CouponRewardType::ITEM);
        $testItem->setRewardData([
            'rarity' => 'UNCOMMON',
            'name' => 'Test Coupon Helmet',
            'type' => 'helmet',
            'stats' => [
                'strongPoints' => 2,
                'agilityPoints' => 2,
                'criticalChancePoints' => 1,
                'healthPoints' => 5,
            ],
        ]);
        $manager->persist($testItem);

        $manager->flush();
    }
}
