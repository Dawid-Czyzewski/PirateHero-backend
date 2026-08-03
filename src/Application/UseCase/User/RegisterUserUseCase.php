<?php

declare(strict_types=1);

namespace App\Application\UseCase\User;

use App\Application\Command\User\RegisterUserCommand;
use App\Application\Port\SendRegistrationEmailPort;
use App\Domain\Constants\EconomyConstants;
use App\Entity\User;
use App\Entity\UserBaseStatistics;
use App\Entity\UserCapacities;
use App\Entity\UserRefill;
use App\Entity\UserSkillPointsPrices;
use App\Entity\UserStatistics;
use App\Enum\RefillType;
use App\Exception\BusinessRuleException;
use App\Exception\ResourceNotFoundException;
use App\Repository\LevelRepository;
use App\Repository\UserRepository;
use App\Service\Economy\BoosterService;
use App\Service\Economy\StorageService;
use App\Service\Economy\UserEquipmentService;
use App\Service\Economy\UserStoreService;
use App\Service\Progression\MissionService;
use App\Service\Progression\TitleService;
use App\Service\Progression\TrainingService;
use App\Service\Progression\WorkService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class RegisterUserUseCase
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private LevelRepository $levelRepository,
        private UserRepository $userRepository,
        private SendRegistrationEmailPort $sendRegistrationEmail,
        private MissionService $missionService,
        private WorkService $workService,
        private StorageService $storageService,
        private UserStoreService $userStoreService,
        private TrainingService $trainingService,
        private UserEquipmentService $userEquipmentService,
        private BoosterService $boosterService,
        private TitleService $titleService,
    ) {
    }

    public function execute(RegisterUserCommand $command): User
    {
        if ($this->userRepository->findOneBy(['email' => $command->email])) {
            throw new BusinessRuleException('emailIsAlreadyTaken');
        }

        if ($this->userRepository->findOneBy(['username' => $command->username])) {
            throw new BusinessRuleException('usernameIsAlreadyTaken');
        }

        $connection = $this->entityManager->getConnection();
        $connection->beginTransaction();
        try {
            $user = new User();
            $user->setEmail($command->email);
            $hashedPassword = $this->passwordHasher->hashPassword($user, $command->password);
            $user->setPassword($hashedPassword);
            $user->setUsername($command->username);
            $user->setAvatarName($this->normalizeAvatarFileKey($command->avatarName));

            $level = $this->levelRepository->findOneBy(['name' => '1']);
            if (!$level) {
                throw new ResourceNotFoundException('levelOneNotFound');
            }
            $user->setLevel($level);
            $user->setTrainingPoints(EconomyConstants::STARTER_TRAINING_POINTS);
            $user->setDuelPoints(EconomyConstants::STARTER_DUEL_POINTS);
            $user->setFamePoints(EconomyConstants::STARTER_FAME_POINTS);

            $stat = new UserBaseStatistics();
            $stat->setStrength(EconomyConstants::STARTER_BASE_STAT);
            $stat->setAgility(EconomyConstants::STARTER_BASE_STAT);
            $stat->setIntelligence(EconomyConstants::STARTER_BASE_STAT);
            $stat->setEndurance(EconomyConstants::STARTER_BASE_STAT);
            $stat->setLuck(EconomyConstants::STARTER_BASE_STAT);
            $user->setUserBaseStatistics($stat);
            $this->entityManager->persist($stat);

            $capacities = new UserCapacities();
            $capacities->setEnergyPoints(EconomyConstants::BASE_ENERGY_CAPACITY);
            $capacities->setTrainingPoints(EconomyConstants::BASE_TRAINING_CAPACITY);
            $capacities->setFightPoints(EconomyConstants::BASE_FIGHT_CAPACITY);
            $user->setUserCapacities($capacities);
            $this->entityManager->persist($capacities);

            $prices = new UserSkillPointsPrices();
            $prices->setEndurancePointsPrice(EconomyConstants::STARTER_SKILL_POINT_PRICE);
            $prices->setStrengthPointsPrice(EconomyConstants::STARTER_SKILL_POINT_PRICE);
            $prices->setAgilityPointsPrice(EconomyConstants::STARTER_SKILL_POINT_PRICE);
            $prices->setIntelligencePointsPrice(EconomyConstants::STARTER_SKILL_POINT_PRICE);
            $prices->setLuckPointsPrice(EconomyConstants::STARTER_SKILL_POINT_PRICE);
            $prices->setUser($user);
            $user->setUserSkillPointsPrices($prices);
            $this->entityManager->persist($prices);

            $userStatistics = new UserStatistics();
            $userStatistics->setUser($user);
            $userStatistics->setLevelsReached(EconomyConstants::STARTER_LEVELS_REACHED);
            $user->setUserStatistics($userStatistics);
            $this->entityManager->persist($userStatistics);

            $refillTypes = [RefillType::ENERGY, RefillType::TRAINING, RefillType::FIGHT];
            foreach ($refillTypes as $refillType) {
                $userRefill = new UserRefill();
                $userRefill->setType($refillType);
                $userRefill->setRefillCount(0);
                $userRefill->setLastRefillDate(null);
                $user->addUserRefill($userRefill);
                $this->entityManager->persist($userRefill);
            }

            $this->missionService->generateMissionsForUser($user);
            $this->workService->generateWorksForUser($user);
            $this->trainingService->generateTrainingsForUser($user);

            $storage = $this->storageService->createEmptyStorageForUser($user);
            $user->setStorage($storage);

            $equipment = $this->userEquipmentService->createEmptyEquipmentForUser($user);
            $user->setUserEquipment($equipment);

            $store = $this->userStoreService->createUserStore($user);
            $user->setUserStore($store);

            $this->boosterService->generateAvailableBoostersForUser($user);

            $this->entityManager->persist($user);
            $this->entityManager->flush();
            $this->titleService->syncUnlocks($user);
            $this->titleService->equipRookieIfNoneEquipped($user);
            $connection->commit();
        } catch (\Throwable $e) {
            $connection->rollBack();
            throw $e;
        }

        $this->sendRegistrationEmail->sendRegistrationEmail(
            $user->getEmail(),
            $user->getUsername(),
            $user->getActivateToken(),
            $command->frontendUrl
        );

        return $user;
    }

    private function normalizeAvatarFileKey(string $avatarName): string
    {
        $value = strtolower(trim($avatarName));

        if ($value === '') {
            return 'avatar1';
        }

        if (preg_match('/^avatar(?:10|[1-9])$/', $value) === 1) {
            return $value;
        }

        return match ($value) {
            'captain' => 'avatar1',
            'boatswain' => 'avatar2',
            'navigator' => 'avatar3',
            'rogue' => 'avatar4',
            'buccaneer' => 'avatar5',
            'admiral' => 'avatar6',
            'captainess' => 'avatar7',
            'sorceress' => 'avatar8',
            'scout' => 'avatar9',
            'warrior' => 'avatar10',
            default => 'avatar1',
        };
    }
}
