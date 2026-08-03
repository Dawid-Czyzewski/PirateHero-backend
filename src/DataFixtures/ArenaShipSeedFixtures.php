<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Level;
use App\Entity\Ship;
use App\Entity\ShipMember;
use App\Entity\User;
use App\Entity\UserBaseStatistics;
use App\Entity\UserCapacities;
use App\Entity\UserStatistics;
use App\Entity\WearableItem;
use App\Enum\ShipRole;
use App\Enum\WearableItemRarity;
use App\Enum\WearableItemType;
use App\Service\Economy\StorageService;
use App\Service\Economy\UserEquipmentService;
use App\Service\Economy\WearableRewardFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;


final class ArenaShipSeedFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    public const PLAIN_PASSWORD = 'TestPass123';

    public const EMAIL_DOMAIN = 'seed.famegame.local';

    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
        private StorageService $storageService,
        private UserEquipmentService $userEquipmentService,
        private WearableRewardFactory $wearableRewardFactory,
    ) {
    }

    public static function getGroups(): array
    {
        return ['arena', 'seed'];
    }

    public function getDependencies(): array
    {
        return [LevelFixtures::class];
    }

    public function load(ObjectManager $manager): void
    {
        $em = $this->assertEntityManager($manager);

        $users = [];
        $createdAny = false;
        foreach ($this->playerDefinitions() as $def) {
            $user = $em->getRepository(User::class)->findOneBy([
                'email' => $this->email($def['key']),
            ]);
            if ($user === null) {
                $user = $this->createActivatedUser($em, $def);
                $createdAny = true;
            }
            $this->ensureSeedEquipment($em, $user, $def['level']);
            $users[$def['key']] = $user;
        }

        $em->flush();

        if ($createdAny) {
            foreach ($this->shipDefinitions() as $shipDef) {
                $this->createShipWithCrew($em, $shipDef, $users);
            }
            $em->flush();
        }
    }

    /**
     * @return list<array{
     *   key: string,
     *   username: string,
     *   level: int,
     *   fame: int,
     *   gold: int,
     *   avatar: string,
     *   stats: array{strength: int, agility: int, endurance: int, intelligence: int, luck: int}
     * }>
     */
    private function playerDefinitions(): array
    {
        $defs = [];
        $roster = [
            ['corsair_01', 'Corsair_Rex', 1, 0, 500, 'avatar1', 30, 30, 30, 30, 30],
            ['corsair_02', 'SeaDog_Ann', 2, 30, 800, 'avatar7', 35, 40, 32, 28, 33],
            ['corsair_03', 'BlackTide', 3, 90, 1200, 'avatar2', 45, 38, 50, 30, 40],
            ['corsair_04', 'SaltBeard', 4, 150, 2000, 'avatar3', 50, 42, 55, 35, 38],
            ['corsair_05', 'RedCannon', 5, 220, 2500, 'avatar5', 60, 48, 58, 40, 45],
            ['corsair_06', 'MistWalker', 6, 300, 3000, 'avatar9', 55, 70, 45, 50, 60],
            ['corsair_07', 'IronHull', 8, 450, 4000, 'avatar6', 80, 50, 90, 40, 35],
            ['corsair_08', 'LuckyDice', 7, 380, 3500, 'avatar4', 40, 55, 48, 45, 95],
            ['corsair_09', 'StormWitch', 10, 600, 5000, 'avatar8', 48, 62, 52, 88, 55],
            ['corsair_10', 'DeckHand_Jo', 3, 60, 900, 'avatar10', 32, 35, 40, 30, 28],
            ['corsair_11', 'PortRat', 5, 180, 1500, 'avatar2', 42, 58, 44, 36, 50],
            ['corsair_12', 'KrakenBite', 12, 900, 8000, 'avatar1', 95, 70, 100, 55, 60],
            ['corsair_13', 'QuietOar', 4, 120, 1100, 'avatar3', 38, 45, 42, 40, 42],
            ['corsair_14', 'GoldTooth', 9, 520, 6000, 'avatar5', 70, 55, 65, 48, 70],
            ['corsair_15', 'FogLookout', 6, 260, 2200, 'avatar9', 36, 80, 40, 55, 48],
            ['corsair_16', 'BarrelKing', 2, 40, 700, 'avatar4', 34, 32, 48, 28, 30],
            ['corsair_17', 'SirenCall', 11, 750, 7000, 'avatar7', 52, 68, 58, 92, 65],
            ['corsair_18', 'LoneWolf', 15, 1200, 10000, 'avatar6', 110, 85, 105, 70, 75],
        ];

        foreach ($roster as $row) {
            $defs[] = [
                'key' => $row[0],
                'username' => $row[1],
                'level' => $row[2],
                'fame' => $row[3],
                'gold' => $row[4],
                'avatar' => $row[5],
                'stats' => [
                    'strength' => $row[6],
                    'agility' => $row[7],
                    'endurance' => $row[8],
                    'intelligence' => $row[9],
                    'luck' => $row[10],
                ],
            ];
        }

        return $defs;
    }

    /**
     * @return list<array{title: string, description: string, fame: int, open: bool, owner: string, manager: ?string, members: list<string>}>
     */
    private function shipDefinitions(): array
    {
        return [
            [
                'title' => 'Czarna Perła Seed',
                'description' => 'Seed — ranking / załoga (arena).',
                'fame' => 2400,
                'open' => false,
                'owner' => 'corsair_12',
                'manager' => 'corsair_09',
                'members' => ['corsair_05', 'corsair_08', 'corsair_11'],
            ],
            [
                'title' => 'Mglista Fregata',
                'description' => 'Seed — otwarty nabór do testów join.',
                'fame' => 980,
                'open' => true,
                'owner' => 'corsair_07',
                'manager' => 'corsair_06',
                'members' => ['corsair_03', 'corsair_10', 'corsair_16'],
            ],
            [
                'title' => 'Złoty Galeon',
                'description' => 'Seed — średnia sława, pełniejsza załoga.',
                'fame' => 1650,
                'open' => false,
                'owner' => 'corsair_14',
                'manager' => null,
                'members' => ['corsair_04', 'corsair_13', 'corsair_15', 'corsair_02'],
            ],
            [
                'title' => 'Samotny Żagiel',
                'description' => 'Seed — mała załoga (kapitan + 1).',
                'fame' => 420,
                'open' => true,
                'owner' => 'corsair_17',
                'manager' => null,
                'members' => ['corsair_01'],
            ],
        ];
    }

    /**
     * @param array{
     *   key: string,
     *   username: string,
     *   level: int,
     *   fame: int,
     *   gold: int,
     *   avatar: string,
     *   stats: array{strength: int, agility: int, endurance: int, intelligence: int, luck: int}
     * } $def
     */
    private function createActivatedUser(EntityManagerInterface $em, array $def): User
    {
        $level = $em->getRepository(Level::class)->findOneBy(['name' => (string) $def['level']]);
        if ($level === null) {
            throw new \RuntimeException(sprintf('Missing level %d — run LevelFixtures first.', $def['level']));
        }

        $user = new User();
        $user->setEmail($this->email($def['key']));
        $user->setUsername($def['username']);
        $user->setPassword($this->passwordHasher->hashPassword($user, self::PLAIN_PASSWORD));
        $user->setActivateToken(null);
        $user->setAvatarName($def['avatar']);
        $user->setLevel($level);
        $user->setGold($def['gold']);
        $user->setDiamonds(50);
        $user->setExperiencePoints(max(0, ($def['level'] - 1) * 80));
        $user->setEnergyPoints(100);
        $user->setTrainingPoints(20);
        $user->setDuelPoints(20);
        $user->setFamePoints($def['fame']);

        $base = new UserBaseStatistics();
        $base->setStrength($def['stats']['strength']);
        $base->setAgility($def['stats']['agility']);
        $base->setEndurance($def['stats']['endurance']);
        $base->setIntelligence($def['stats']['intelligence']);
        $base->setLuck($def['stats']['luck']);
        $base->setUser($user);
        $user->setUserBaseStatistics($base);
        $em->persist($base);

        $capacities = new UserCapacities();
        $capacities->setEnergyPoints(100);
        $capacities->setTrainingPoints(20);
        $capacities->setFightPoints(20);
        $capacities->setUser($user);
        $user->setUserCapacities($capacities);
        $em->persist($capacities);

        $statistics = new UserStatistics();
        $statistics->setUser($user);
        $statistics->setLevelsReached($def['level']);
        $user->setUserStatistics($statistics);
        $em->persist($statistics);

        $em->persist($user);
        $em->flush();

        if ($user->getStorage() === null) {
            $storage = $this->storageService->createEmptyStorageForUser($user);
            $user->setStorage($storage);
        }
        if ($user->getUserEquipment() === null) {
            $equipment = $this->userEquipmentService->createEmptyEquipmentForUser($user);
            $user->setUserEquipment($equipment);
        }

        $this->ensureSeedEquipment($em, $user, $def['level']);

        return $user;
    }

    private function ensureSeedEquipment(EntityManagerInterface $em, User $user, int $level): void
    {
        if ($user->getUserEquipment() === null) {
            $equipment = $this->userEquipmentService->createEmptyEquipmentForUser($user);
            $user->setUserEquipment($equipment);
            $em->flush();
        }

        $rarity = $this->rarityForLevel($level);
        $modifier = $this->wearableRewardFactory->defaultModifier($rarity);
        $slotCount = $level <= 2 ? 3 : ($level <= 5 ? 5 : 6);
        $types = \array_slice(WearableItemType::orderedCases(), 0, $slotCount);

        foreach ($types as $type) {
            $slot = null;
            foreach ($user->getUserEquipment()->getUserEquipmentSlots() as $candidate) {
                if ($candidate->getType() === $type) {
                    $slot = $candidate;
                    break;
                }
            }
            if ($slot === null || $slot->getWearableItem() !== null) {
                continue;
            }

            $statistics = $this->wearableRewardFactory->createItemStats($level, $modifier);
            $em->persist($statistics);

            $item = new WearableItem();
            $item->setType($type);
            $item->setRarity($rarity);
            $item->setPrice(max(20, (int) round((25 * $level + 15) * $modifier)));
            $item->setName(sprintf('Seed %s L%d', ucfirst($type->value), $level));
            $item->setStatistics($statistics);
            $em->persist($item);

            $slot->equip($item);
            $em->persist($slot);
        }
    }

    private function rarityForLevel(int $level): WearableItemRarity
    {
        return match (true) {
            $level >= 12 => WearableItemRarity::LEGENDARY,
            $level >= 9 => WearableItemRarity::EPIC,
            $level >= 5 => WearableItemRarity::RARE,
            $level >= 3 => WearableItemRarity::UNCOMMON,
            default => WearableItemRarity::COMMON,
        };
    }

    /**
     * @param array{title: string, description: string, fame: int, open: bool, owner: string, manager: ?string, members: list<string>} $shipDef
     * @param array<string, User> $users
     */
    private function createShipWithCrew(EntityManagerInterface $em, array $shipDef, array $users): void
    {
        $existing = $em->getRepository(Ship::class)->findOneBy(['title' => $shipDef['title']]);
        if ($existing !== null) {
            return;
        }

        $ship = new Ship();
        $ship->setTitle($shipDef['title']);
        $ship->setDescription($shipDef['description']);
        $ship->setFamePoints($shipDef['fame']);
        $ship->setRequiresInvitation(!$shipDef['open']);
        $ship->setGold(2500);
        $ship->setDiamonds(25);
        $em->persist($ship);

        $this->addMember($em, $ship, $users[$shipDef['owner']], ShipRole::OWNER);

        if ($shipDef['manager'] !== null) {
            $this->addMember($em, $ship, $users[$shipDef['manager']], ShipRole::MANAGER);
        }

        foreach ($shipDef['members'] as $memberKey) {
            $this->addMember($em, $ship, $users[$memberKey], ShipRole::MEMBER);
        }
    }

    private function addMember(EntityManagerInterface $em, Ship $ship, User $user, ShipRole $role): void
    {
        $member = new ShipMember();
        $member->setShip($ship);
        $member->setUser($user);
        $member->setRole($role);
        $ship->addMember($member);
        $user->addShipMember($member);
        $em->persist($member);
    }

    private function email(string $key): string
    {
        return $key.'@'.self::EMAIL_DOMAIN;
    }

    private function assertEntityManager(ObjectManager $manager): EntityManagerInterface
    {
        if (!$manager instanceof EntityManagerInterface) {
            throw new \InvalidArgumentException('Expected EntityManagerInterface.');
        }

        return $manager;
    }
}
