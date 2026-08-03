<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Level;
use App\Entity\QuestTemplate;
use App\Entity\User;
use App\Entity\UserQuest;
use App\Entity\UserStatistics;
use App\Enum\QuestCategory;
use App\Enum\QuestRewardType;
use App\Repository\UserQuestRepository;
use App\Repository\UserRepository;
use App\Service\Economy\StorageService;
use App\Service\Progression\QuestTemplateDefaults;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;


final class UserQuestShowcaseFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    public const TARGET_EMAIL = QuestTemplateDefaults::TEST_ACCOUNT_EMAIL;

    public const NEW_USER_PLAIN_PASSWORD = 'Showcase_Quests_1';

    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
        private StorageService $storageService,
    ) {
    }

    public static function getGroups(): array
    {
        return ['showcase'];
    }

    public function getDependencies(): array
    {
        return [LevelFixtures::class];
    }

    public function load(ObjectManager $manager): void
    {
        $em = $this->assertEntityManager($manager);
        $level = $em->getRepository(Level::class)->findOneBy(['name' => '1']);
        if ($level === null) {
            throw new \RuntimeException('LevelFixtures must run before UserQuestShowcaseFixtures.');
        }

        /** @var UserRepository $users */
        $users = $em->getRepository(User::class);
        $user = $users->findOneBy(['email' => self::TARGET_EMAIL]);
        if ($user === null) {
            $user = $this->createShowcaseUser($em, $level);
        }

        $this->ensureUserStatistics($em, $user);
        $this->ensureStorage($em, $user);

        foreach ($this->templateDefinitions() as $def) {
            $this->findOrCreateTemplate($em, $def);
        }

        foreach ($this->userQuestDefinitions() as $def) {
            $this->ensureUserQuest($em, $user, $def);
        }

        $em->flush();
    }

    /** @return array<int, array{title: string, description: string, category: QuestCategory, targetValue: int, rewardType: QuestRewardType, rewardAmount: int, order: int}> */
    private function templateDefinitions(): array
    {
        return [
            [
                'title' => 'Showcase: nagroda złotem',
                'description' => 'Nieaktywne — seed (grupa showcase).',
                'category' => QuestCategory::GOLD_SPENT,
                'targetValue' => 1,
                'rewardType' => QuestRewardType::GOLD,
                'rewardAmount' => 250,
                'order' => 9901,
            ],
            [
                'title' => 'Showcase: doświadczenie',
                'description' => 'Nieaktywne — seed (grupa showcase).',
                'category' => QuestCategory::LEVEL_UP,
                'targetValue' => 1,
                'rewardType' => QuestRewardType::EXPERIENCE,
                'rewardAmount' => 120,
                'order' => 9902,
            ],
            [
                'title' => 'Showcase: diamenty',
                'description' => 'Nieaktywne — seed (grupa showcase).',
                'category' => QuestCategory::FIGHTS_WON,
                'targetValue' => 1,
                'rewardType' => QuestRewardType::diamonds,
                'rewardAmount' => 3,
                'order' => 9903,
            ],
            [
                'title' => 'Showcase: losowy przedmiot',
                'description' => 'Nieaktywne — seed (grupa showcase).',
                'category' => QuestCategory::FIGHTS_LOST,
                'targetValue' => 1,
                'rewardType' => QuestRewardType::ITEM,
                'rewardAmount' => 1,
                'order' => 9904,
            ],
            [
                'title' => 'Showcase (do odbioru): złoto',
                'description' => 'Ukończone — kliknij „Odbierz” w grze.',
                'category' => QuestCategory::GOLD_SPENT,
                'targetValue' => 1,
                'rewardType' => QuestRewardType::GOLD,
                'rewardAmount' => 180,
                'order' => 9911,
            ],
            [
                'title' => 'Showcase (do odbioru): doświadczenie',
                'description' => 'Ukończone — kliknij „Odbierz” w grze.',
                'category' => QuestCategory::LEVEL_UP,
                'targetValue' => 1,
                'rewardType' => QuestRewardType::EXPERIENCE,
                'rewardAmount' => 90,
                'order' => 9912,
            ],
            [
                'title' => 'Showcase (do odbioru): diamenty',
                'description' => 'Ukończone — kliknij „Odbierz” w grze.',
                'category' => QuestCategory::FIGHTS_WON,
                'targetValue' => 1,
                'rewardType' => QuestRewardType::diamonds,
                'rewardAmount' => 5,
                'order' => 9913,
            ],
            [
                'title' => 'Showcase (do odbioru): losowy przedmiot',
                'description' => 'Ukończone — kliknij „Odbierz” w grze.',
                'category' => QuestCategory::FIGHTS_LOST,
                'targetValue' => 1,
                'rewardType' => QuestRewardType::ITEM,
                'rewardAmount' => 1,
                'order' => 9914,
            ],
            [
                'title' => 'Showcase (do odbioru): losowy przedmiot II',
                'description' => 'Ukończone — losowy przedmiot (seed showcase).',
                'category' => QuestCategory::FIGHTS_WON,
                'targetValue' => 1,
                'rewardType' => QuestRewardType::ITEM,
                'rewardAmount' => 1,
                'order' => 9915,
            ],
            [
                'title' => 'Showcase (do odbioru): losowy przedmiot III',
                'description' => 'Ukończone — losowy przedmiot (seed showcase).',
                'category' => QuestCategory::GOLD_SPENT,
                'targetValue' => 1,
                'rewardType' => QuestRewardType::ITEM,
                'rewardAmount' => 1,
                'order' => 9916,
            ],
            [
                'title' => 'Showcase (do odbioru): losowy przedmiot IV',
                'description' => 'Ukończone — losowy przedmiot (seed showcase).',
                'category' => QuestCategory::LEVEL_UP,
                'targetValue' => 1,
                'rewardType' => QuestRewardType::ITEM,
                'rewardAmount' => 1,
                'order' => 9917,
            ],
            [
                'title' => 'Showcase (do odbioru): losowy przedmiot V',
                'description' => 'Ukończone — losowy przedmiot (dodatkowy seed dla test@wp.pl).',
                'category' => QuestCategory::FIGHTS_LOST,
                'targetValue' => 1,
                'rewardType' => QuestRewardType::ITEM,
                'rewardAmount' => 1,
                'order' => 9918,
            ],
            [
                'title' => 'Showcase (do odbioru): losowy przedmiot VI',
                'description' => 'Ukończone — losowy przedmiot (dodatkowy seed dla test@wp.pl).',
                'category' => QuestCategory::FIGHTS_WON,
                'targetValue' => 1,
                'rewardType' => QuestRewardType::ITEM,
                'rewardAmount' => 1,
                'order' => 9919,
            ],
            [
                'title' => 'Showcase (do odbioru): losowy przedmiot VII',
                'description' => 'Ukończone — losowy przedmiot (dodatkowy seed dla test@wp.pl).',
                'category' => QuestCategory::GOLD_SPENT,
                'targetValue' => 1,
                'rewardType' => QuestRewardType::ITEM,
                'rewardAmount' => 1,
                'order' => 9920,
            ],
            [
                'title' => 'Showcase (do odbioru): losowy przedmiot VIII',
                'description' => 'Ukończone — losowy przedmiot (dodatkowy seed dla test@wp.pl).',
                'category' => QuestCategory::LEVEL_UP,
                'targetValue' => 1,
                'rewardType' => QuestRewardType::ITEM,
                'rewardAmount' => 1,
                'order' => 9921,
            ],
        ];
    }

    /**
     * @return list<array{templateTitle: string, rewardClaimed: bool, completedAt: \DateTimeImmutable}>
     */
    private function userQuestDefinitions(): array
    {
        $baseHistory = new \DateTimeImmutable('2025-01-15T12:00:00+00:00');
        $baseClaim = new \DateTimeImmutable('2025-01-16T10:00:00+00:00');

        return [
            ['templateTitle' => 'Showcase: nagroda złotem', 'rewardClaimed' => true, 'completedAt' => $baseHistory->modify('+0 hours')],
            ['templateTitle' => 'Showcase: doświadczenie', 'rewardClaimed' => true, 'completedAt' => $baseHistory->modify('+1 hours')],
            ['templateTitle' => 'Showcase: diamenty', 'rewardClaimed' => true, 'completedAt' => $baseHistory->modify('+2 hours')],
            ['templateTitle' => 'Showcase: losowy przedmiot', 'rewardClaimed' => true, 'completedAt' => $baseHistory->modify('+3 hours')],

            ['templateTitle' => 'Showcase (do odbioru): złoto', 'rewardClaimed' => false, 'completedAt' => $baseClaim->modify('+0 hours')],
            ['templateTitle' => 'Showcase (do odbioru): doświadczenie', 'rewardClaimed' => false, 'completedAt' => $baseClaim->modify('+1 hours')],
            ['templateTitle' => 'Showcase (do odbioru): losowy przedmiot', 'rewardClaimed' => false, 'completedAt' => $baseClaim->modify('+3 hours')],
        ];
    }

    private function assertEntityManager(ObjectManager $manager): EntityManagerInterface
    {
        if (!$manager instanceof EntityManagerInterface) {
            throw new \InvalidArgumentException('Expected EntityManagerInterface.');
        }

        return $manager;
    }

    private function createShowcaseUser(EntityManagerInterface $em, Level $level): User
    {
        $user = new User();
        $user->setEmail(self::TARGET_EMAIL);
        $user->setUsername(str_replace(['@', '.'], '_', self::TARGET_EMAIL));
        $user->setPassword($this->passwordHasher->hashPassword($user, self::NEW_USER_PLAIN_PASSWORD));
        $user->setActivateToken(null);
        $user->setLevel($level);
        $user->setGold(50_000);
        $user->setDiamonds(500);
        $user->setExperiencePoints(100);
        $user->setEnergyPoints(100);
        $user->setTrainingPoints(50);
        $user->setDuelPoints(10);
        $user->setFamePoints(10);
        $em->persist($user);
        $em->flush();

        return $user;
    }

    private function ensureUserStatistics(EntityManagerInterface $em, User $user): void
    {
        if ($user->getUserStatistics() !== null) {
            return;
        }

        $stats = new UserStatistics();
        $stats->setUser($user);
        $stats->setLevelsReached($user->getLevel() ? (int) $user->getLevel()->getName() : 1);
        $user->setUserStatistics($stats);
        $em->persist($stats);
        $em->flush();
    }

    private function ensureStorage(EntityManagerInterface $em, User $user): void
    {
        if ($user->getStorage() !== null) {
            return;
        }

        $storage = $this->storageService->createEmptyStorageForUser($user);
        $user->setStorage($storage);
        $em->flush();
    }

    /**
     * @param array{title: string, description: string, category: QuestCategory, targetValue: int, rewardType: QuestRewardType, rewardAmount: int, order: int} $def
     */
    private function findOrCreateTemplate(EntityManagerInterface $em, array $def): QuestTemplate
    {
        $repo = $em->getRepository(QuestTemplate::class);
        $existing = $repo->findOneBy(['title' => $def['title']]);
        if ($existing instanceof QuestTemplate) {
            return $existing;
        }

        $t = new QuestTemplate();
        $t->setTitle($def['title']);
        $t->setDescription($def['description']);
        $t->setCategory($def['category']);
        $t->setTargetValue($def['targetValue']);
        $t->setRewardType($def['rewardType']);
        $t->setRewardAmount($def['rewardAmount']);
        $t->setIsActive(false);
        $t->setOrder($def['order']);
        $em->persist($t);
        $em->flush();

        return $t;
    }

    /**
     * @param array{templateTitle: string, rewardClaimed: bool, completedAt: \DateTimeImmutable} $def
     */
    private function ensureUserQuest(EntityManagerInterface $em, User $user, array $def): void
    {
        $template = $em->getRepository(QuestTemplate::class)->findOneBy(['title' => $def['templateTitle']]);
        if (!$template instanceof QuestTemplate) {
            throw new \RuntimeException('Missing quest template: '.$def['templateTitle']);
        }

        /** @var UserQuestRepository $uqRepo */
        $uqRepo = $em->getRepository(UserQuest::class);
        $existing = $uqRepo->findOneBy(['user' => $user, 'questTemplate' => $template]);
        if ($existing !== null) {
            return;
        }

        $uq = new UserQuest();
        $uq->setUser($user);
        $uq->setQuestTemplate($template);
        $uq->setCurrentProgress($template->getTargetValue());
        $uq->setIsRewardClaimed($def['rewardClaimed']);
        $uq->setCompletedAt($def['completedAt']);
        $em->persist($uq);
    }
}
