<?php

declare(strict_types=1);

namespace App\Entity;

use App\Exception\BusinessRuleException;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\Column(type: Types::GUID)]
    #[Groups(['user:read'])]
    private string $id;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column]
    #[Groups(['user:read'])]
    private ?int $experiencePoints = null;

    #[ORM\Column(length: 30, unique: true)]
    #[Groups(['user:read'])]
    private ?string $username = null;

    #[ORM\Column(length: 64, nullable: true)]
    #[Groups(['user:read'])]
    private ?string $avatarName = null;

    #[ORM\Column]
    private ?\DateTime $add_date = null;

    #[ORM\Column(name: 'diamonds')]
    #[Groups(['user:read'])]
    #[SerializedName('diamonds')]
    private ?int $diamonds = null;

    #[ORM\Column]
    #[Groups(['user:read'])]
    private ?int $gold = null;

    #[ORM\Column(length: 255, unique: true, nullable: true)]
    private ?string $activateToken = null;

    #[ORM\Column(length: 255, unique: true, nullable: true)]
    private ?string $passwordResetToken = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $passwordResetTokenExpiresAt = null;

    #[ORM\Column]
    #[Groups(['user:read'])]
    private ?int $energyPoints = null;

    #[ORM\Column]
    #[Groups(['user:read'])]
    private ?int $trainingPoints = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['user:read'])]
    private ?Level $level = null;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Mission::class, cascade: ['persist', 'remove'])]
    #[Groups(['user:read'])]
    private Collection $missions;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Training::class, cascade: ['persist', 'remove'])]
    #[Groups(['user:read'])]
    private Collection $trainings;

    #[ORM\OneToOne(targetEntity: UserActualActivity::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['user:read'])]
    private ?UserActualActivity $currentActivity = null;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Work::class, cascade: ['persist', 'remove'])]
    #[Groups(['user:read'])]
    private Collection $works;

    #[ORM\OneToOne(mappedBy: 'user', cascade: ['persist', 'remove'])]
    #[Groups(['user:read'])]
    private ?UserBaseStatistics $userBaseStatistics = null;

    #[ORM\Column]
    #[Groups(['user:read'])]
    private ?int $freeSkillPointsAvailable = null;

    #[ORM\OneToOne(mappedBy: 'user', cascade: ['persist', 'remove'])]
    #[Groups(['user:read'])]
    private ?UserSkillPointsPrices $userSkillPointsPrices = null;

    #[ORM\OneToOne(mappedBy: 'user', targetEntity: UserStorage::class, cascade: ['persist', 'remove'])]
    #[Groups(['user:read'])]
    private ?UserStorage $storage = null;

    #[ORM\OneToOne(mappedBy: 'user', cascade: ['persist', 'remove'])]
    #[Groups(['user:read'])]
    private ?UserEquipment $userEquipment = null;

    #[ORM\OneToOne(mappedBy: 'user', cascade: ['persist', 'remove'])]
    private ?UserStore $userStore = null;

    #[ORM\Column]
    #[Groups(['user:read'])]
    private ?int $duelPoints = null;

    #[ORM\Column]
    #[Groups(['user:read'])]
    private ?int $famePoints = null;

    #[ORM\OneToMany(targetEntity: UsersFight::class, mappedBy: 'attacker', orphanRemoval: true)]
    private Collection $userFightsAsAttacker;

    #[ORM\OneToMany(targetEntity: UsersFight::class, mappedBy: 'defender', orphanRemoval: true)]
    private Collection $userFightsAsDefender;

    #[ORM\OneToOne(mappedBy: 'user', cascade: ['persist', 'remove'])]
    #[Groups(['user:read'])]
    private ?UserCapacities $userCapacities = null;

    #[ORM\OneToMany(targetEntity: UserBooster::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
    #[Groups(['user:read'])]
    private Collection $userBoosters;

    #[ORM\OneToMany(targetEntity: UserAvailableBooster::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
    #[Groups(['user:read'])]
    private Collection $userAvailableBoosters;

    #[ORM\OneToMany(targetEntity: ShipMember::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
    private Collection $shipMembers;

    #[ORM\OneToOne(mappedBy: 'user', cascade: ['persist', 'remove'])]
    private ?UserStatistics $userStatistics = null;

    #[ORM\OneToMany(targetEntity: UserQuest::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
    private Collection $userQuests;

    /** @var Collection<int, UserDungeonProgress> */
    #[ORM\OneToMany(targetEntity: UserDungeonProgress::class, mappedBy: 'user', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $dungeonProgressEntries;

    #[ORM\ManyToOne(targetEntity: PlayerTitle::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?PlayerTitle $equippedTitle = null;

    #[ORM\OneToMany(targetEntity: UserRefill::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
    private Collection $userRefills;

    #[ORM\OneToMany(targetEntity: UserCoupon::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
    #[Groups(['user:read'])]
    private Collection $userCoupons;

    #[ORM\OneToOne(targetEntity: UserDailyReward::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
    private ?UserDailyReward $dailyRewardProgress = null;

    public function __construct()
    {
        $this->id = Uuid::v4()->toRfc4122();
        $this->add_date = new \DateTime();
        $this->gold = 0;
        $this->experiencePoints = 0;
        $this->diamonds = 0;
        $this->roles = ['ROLE_USER'];
        $this->energyPoints = 100;
        $this->freeSkillPointsAvailable = 0;
        $this->activateToken = $this->generateActivateToken();
        $this->missions = new ArrayCollection();
        $this->works = new ArrayCollection();
        $this->trainings = new ArrayCollection();
        $this->userFightsAsAttacker = new ArrayCollection();
        $this->userFightsAsDefender = new ArrayCollection();
        $this->userBoosters = new ArrayCollection();
        $this->userAvailableBoosters = new ArrayCollection();
        $this->shipMembers = new ArrayCollection();
        $this->userQuests = new ArrayCollection();
        $this->dungeonProgressEntries = new ArrayCollection();
        $this->userRefills = new ArrayCollection();
        $this->userCoupons = new ArrayCollection();
    }

    private function generateActivateToken(): string
    {
        return bin2hex(random_bytes(64));
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function eraseCredentials(): void
    {
    }

    public function getExperiencePoints(): ?int
    {
        return $this->experiencePoints;
    }

    public function setExperiencePoints(int $experiencePoints): static
    {
        $this->experiencePoints = $experiencePoints;

        return $this;
    }

    public function addExperiencePoints(int $amount): static
    {
        $this->assertNonNegativeAmount($amount);
        $this->experiencePoints = ($this->experiencePoints ?? 0) + $amount;

        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;

        return $this;
    }

    public function getAvatarName(): ?string
    {
        return $this->avatarName;
    }

    public function setAvatarName(string $avatarName): static
    {
        $this->avatarName = $avatarName;

        return $this;
    }

    public function getAddDate(): ?\DateTime
    {
        return $this->add_date;
    }

    public function setAddDate(\DateTime $add_date): static
    {
        $this->add_date = $add_date;

        return $this;
    }

    public function getDiamonds(): ?int
    {
        return $this->diamonds;
    }

    public function setDiamonds(int $diamonds): static
    {
        $this->diamonds = $diamonds;

        return $this;
    }

    public function addDiamonds(int $amount): static
    {
        $this->assertNonNegativeAmount($amount);
        $this->diamonds = ($this->diamonds ?? 0) + $amount;

        return $this;
    }

    public function spendDiamonds(int $amount, string $errorKey = 'notEnoughDiamonds'): static
    {
        $this->assertNonNegativeAmount($amount);
        if (($this->diamonds ?? 0) < $amount) {
            throw new BusinessRuleException($errorKey);
        }
        $this->diamonds = ($this->diamonds ?? 0) - $amount;

        return $this;
    }

    public function getGold(): ?int
    {
        return $this->gold;
    }

    public function setGold(int $gold): static
    {
        $this->gold = $gold;

        return $this;
    }

    public function addGold(int $amount): static
    {
        $this->assertNonNegativeAmount($amount);
        $this->gold = ($this->gold ?? 0) + $amount;

        return $this;
    }

    public function spendGold(int $amount, string $errorKey = 'notEnoughGold'): static
    {
        $this->assertNonNegativeAmount($amount);
        if (($this->gold ?? 0) < $amount) {
            throw new BusinessRuleException($errorKey);
        }
        $this->gold = ($this->gold ?? 0) - $amount;

        return $this;
    }

    public function getActivateToken(): ?string
    {
        return $this->activateToken;
    }

    public function setActivateToken(?string $activateToken): static
    {
        $this->activateToken = $activateToken;

        return $this;
    }

    public function getPasswordResetToken(): ?string
    {
        return $this->passwordResetToken;
    }

    public function setPasswordResetToken(?string $passwordResetToken): static
    {
        $this->passwordResetToken = $passwordResetToken;

        return $this;
    }

    public function getPasswordResetTokenExpiresAt(): ?\DateTimeImmutable
    {
        return $this->passwordResetTokenExpiresAt;
    }

    public function setPasswordResetTokenExpiresAt(?\DateTimeImmutable $passwordResetTokenExpiresAt): static
    {
        $this->passwordResetTokenExpiresAt = $passwordResetTokenExpiresAt;

        return $this;
    }

    public function issuePasswordResetToken(\DateTimeImmutable $expiresAt): string
    {
        $token = bin2hex(random_bytes(32));
        $this->passwordResetToken = $token;
        $this->passwordResetTokenExpiresAt = $expiresAt;

        return $token;
    }

    public function clearPasswordResetToken(): void
    {
        $this->passwordResetToken = null;
        $this->passwordResetTokenExpiresAt = null;
    }

    public function getEnergyPoints(): ?int
    {
        return $this->energyPoints;
    }

    public function setEnergyPoints(int $energyPoints): static
    {
        $this->energyPoints = $energyPoints;

        return $this;
    }

    public function spendEnergy(int $amount, string $errorKey = 'notEnoughEnergy'): static
    {
        $this->assertNonNegativeAmount($amount);
        if (($this->energyPoints ?? 0) < $amount) {
            throw new BusinessRuleException($errorKey);
        }
        $this->energyPoints = ($this->energyPoints ?? 0) - $amount;

        return $this;
    }

    public function restoreEnergy(int $amount): static
    {
        $this->assertNonNegativeAmount($amount);
        $this->energyPoints = ($this->energyPoints ?? 0) + $amount;

        return $this;
    }

    public function refillEnergy(int $max): static
    {
        $this->energyPoints = max(0, $max);

        return $this;
    }

    public function getLevel(): ?Level
    {
        return $this->level;
    }

    public function setLevel(?Level $level): static
    {
        $this->level = $level;

        return $this;
    }

    public function getMissions(): Collection
    {
        return $this->missions;
    }

    public function addMission(Mission $mission): self
    {
        if (!$this->missions->contains($mission)) {
            $this->missions->add($mission);
            $mission->setUser($this);
        }

        return $this;
    }

    public function removeMission(Mission $mission): self
    {
        if ($this->missions->removeElement($mission)) {
            if ($mission->getUser() === $this) {
                $mission->setUser(null);
            }
        }

        return $this;
    }

    public function getTrainings(): Collection
    {
        return $this->trainings;
    }

    public function addTraining(Training $training): self
    {
        if (!$this->trainings->contains($training)) {
            $this->trainings->add($training);
            $training->setUser($this);
        }

        return $this;
    }

    public function removeTraining(Training $training): self
    {
        if ($this->trainings->removeElement($training)) {
            if ($training->getUser() === $this) {
                $training->setUser(null);
            }
        }

        return $this;
    }

    public function getCurrentActivity(): ?UserActualActivity
    {
        return $this->currentActivity;
    }

    public function setCurrentActivity(?UserActualActivity $currentActivity): self
    {
        $this->currentActivity = $currentActivity;

        return $this;
    }

    public function getWorks(): Collection
    {
        return $this->works;
    }

    public function addWork(Work $work): self
    {
        if (!$this->works->contains($work)) {
            $this->works->add($work);
            $work->setUser($this);
        }

        return $this;
    }

    public function removeWork(Work $work): self
    {
        if ($this->works->removeElement($work)) {
            if ($work->getUser() === $this) {
                $work->setUser(null);
            }
        }

        return $this;
    }

    public function getUserBaseStatistics(): ?UserBaseStatistics
    {
        return $this->userBaseStatistics;
    }

    public function setUserBaseStatistics(UserBaseStatistics $userBaseStatistics): static
    {
        if ($userBaseStatistics->getUser() !== $this) {
            $userBaseStatistics->setUser($this);
        }

        $this->userBaseStatistics = $userBaseStatistics;

        return $this;
    }

    public function getFreeSkillPointsAvailable(): ?int
    {
        return $this->freeSkillPointsAvailable;
    }

    public function setFreeSkillPointsAvailable(int $freeSkillPointsAvailable): static
    {
        $this->freeSkillPointsAvailable = $freeSkillPointsAvailable;

        return $this;
    }

    public function getUserSkillPointsPrices(): ?UserSkillPointsPrices
    {
        return $this->userSkillPointsPrices;
    }

    public function setUserSkillPointsPrices(UserSkillPointsPrices $userSkillPointsPrices): static
    {
        if ($userSkillPointsPrices->getUser() !== $this) {
            $userSkillPointsPrices->setUser($this);
        }

        $this->userSkillPointsPrices = $userSkillPointsPrices;

        return $this;
    }

    public function getStorage(): ?UserStorage
    {
        return $this->storage;
    }

    public function setStorage(UserStorage $storage): static
    {
        $this->storage = $storage;

        return $this;
    }

    public function getUserEquipment(): ?UserEquipment
    {
        return $this->userEquipment;
    }

    public function setUserEquipment(UserEquipment $userEquipment): static
    {
        if ($userEquipment->getUser() !== $this) {
            $userEquipment->setUser($this);
        }

        $this->userEquipment = $userEquipment;

        return $this;
    }

    public function getUserStore(): ?UserStore
    {
        return $this->userStore;
    }

    public function setUserStore(UserStore $userStore): static
    {
        if ($userStore->getUser() !== $this) {
            $userStore->setUser($this);
        }

        $this->userStore = $userStore;

        return $this;
    }

    public function getTrainingPoints(): ?int
    {
        return $this->trainingPoints;
    }

    public function setTrainingPoints(int $trainingPoints): static
    {
        $this->trainingPoints = $trainingPoints;

        return $this;
    }

    public function spendTrainingPoints(int $amount, string $errorKey = 'notEnoughTrainingPoints'): static
    {
        $this->assertNonNegativeAmount($amount);
        if (($this->trainingPoints ?? 0) < $amount) {
            throw new BusinessRuleException($errorKey);
        }
        $this->trainingPoints = ($this->trainingPoints ?? 0) - $amount;

        return $this;
    }

    public function restoreTrainingPoints(int $amount): static
    {
        $this->assertNonNegativeAmount($amount);
        $this->trainingPoints = ($this->trainingPoints ?? 0) + $amount;

        return $this;
    }

    public function addTrainingPoints(int $amount, ?int $cap = null): static
    {
        $this->assertNonNegativeAmount($amount);
        $next = ($this->trainingPoints ?? 0) + $amount;
        $this->trainingPoints = $cap === null ? $next : min(max(0, $next), $cap);

        return $this;
    }

    public function refillTrainingPoints(int $max): static
    {
        $this->trainingPoints = max(0, $max);

        return $this;
    }

    public function getDuelPoints(): ?int
    {
        return $this->duelPoints;
    }

    public function setDuelPoints(int $duelPoints): static
    {
        $this->duelPoints = $duelPoints;

        return $this;
    }

    public function spendDuelPoints(int $amount, string $errorKey = 'notEnoughDuelPoints'): static
    {
        $this->assertNonNegativeAmount($amount);
        if (($this->duelPoints ?? 0) < $amount) {
            throw new BusinessRuleException($errorKey);
        }
        $this->duelPoints = ($this->duelPoints ?? 0) - $amount;

        return $this;
    }

    public function refillDuelPoints(int $max): static
    {
        $this->duelPoints = max(0, $max);

        return $this;
    }

    public function getFamePoints(): ?int
    {
        return $this->famePoints;
    }

    public function setFamePoints(int $famePoints): static
    {
        $this->famePoints = max(0, $famePoints);

        return $this;
    }

    public function addFamePoints(int $amount): static
    {
        $this->famePoints = max(0, ($this->famePoints ?? 0) + $amount);

        return $this;
    }

    private function assertNonNegativeAmount(int $amount): void
    {
        if ($amount < 0) {
            throw new \InvalidArgumentException('Amount must be non-negative.');
        }
    }

    public function getUserFightsAsAttacker(): Collection
    {
        return $this->userFightsAsAttacker;
    }

    public function addUserFightsAsAttacker(UsersFight $userFightsAsAttacker): static
    {
        if (!$this->userFightsAsAttacker->contains($userFightsAsAttacker)) {
            $this->userFightsAsAttacker->add($userFightsAsAttacker);
            $userFightsAsAttacker->setAttacker($this);
        }

        return $this;
    }

    public function removeUserFightsAsAttacker(UsersFight $userFightsAsAttacker): static
    {
        if ($this->userFightsAsAttacker->removeElement($userFightsAsAttacker)) {
            if ($userFightsAsAttacker->getAttacker() === $this) {
                $userFightsAsAttacker->setAttacker(null);
            }
        }

        return $this;
    }

    public function getUserFightsAsDefender(): Collection
    {
        return $this->userFightsAsDefender;
    }

    public function addUserFightsAsDefender(UsersFight $userFightsAsDefender): static
    {
        if (!$this->userFightsAsDefender->contains($userFightsAsDefender)) {
            $this->userFightsAsDefender->add($userFightsAsDefender);
            $userFightsAsDefender->setDefender($this);
        }

        return $this;
    }

    public function removeUserFightsAsDefender(UsersFight $userFightsAsDefender): static
    {
        if ($this->userFightsAsDefender->removeElement($userFightsAsDefender)) {
            if ($userFightsAsDefender->getDefender() === $this) {
                $userFightsAsDefender->setDefender(null);
            }
        }

        return $this;
    }

    public function getTotalStatistics(): array
    {
        $stats = [
            'health' => 0,
            'strength' => 0,
            'agility' => 0,
            'luck' => 0,
            'intelligence' => 0,
        ];

        if ($this->userBaseStatistics) {
            $stats['health'] += $this->userBaseStatistics->getEndurance();
            $stats['strength'] += $this->userBaseStatistics->getStrength();
            $stats['agility'] += $this->userBaseStatistics->getAgility();
            $stats['luck'] += $this->userBaseStatistics->getLuck();
            $stats['intelligence'] += $this->userBaseStatistics->getIntelligence();
        }

        if ($this->userEquipment) {
            foreach ($this->userEquipment->getUserEquipmentSlots() as $slot) {
                $item = $slot->getWearableItem();
                if ($item && $item->getStatistics()) {
                    $itemStats = $item->getStatistics();
                    $stats['health'] += $itemStats->getHealthPoints();
                    $stats['strength'] += $itemStats->getStrongPoints();
                    $stats['agility'] += $itemStats->getAgilityPoints();
                    $stats['luck'] += $itemStats->getCriticalChancePoints();
                    $stats['intelligence'] += $itemStats->getIntelligencePoints();
                }
            }
        }

        $shipMember = $this->getShip();
        $ship = $shipMember?->getShip();
        if ($ship) {
            $skillsUpgrade = $ship->getSkillsUpgrade();
            if ($skillsUpgrade > 0) {
                $multiplier = 1 + ($skillsUpgrade / 100);
                $stats = array_map(
                    static function (int $value) use ($multiplier): int {
                        if ($value <= 0) {
                            return $value;
                        }
                        $raw = $value * $multiplier;
                        $floored = (int) floor($raw + 1e-9);
                        if ($floored > $value) {
                            return $floored;
                        }
                        if ($raw > $value + 1e-9) {
                            return $value + 1;
                        }

                        return $value;
                    },
                    $stats
                );
            }
        }

        $stats['critical'] = $stats['luck'];

        return $stats;
    }

    public function getAverageSkill(): float
    {
        $stats = $this->getTotalStatistics();
        $values = array_values($stats);

        return array_sum($values) / count($values);
    }

    public function getUserCapacities(): ?UserCapacities
    {
        return $this->userCapacities;
    }

    public function setUserCapacities(UserCapacities $userCapacities): static
    {
        if ($userCapacities->getUser() !== $this) {
            $userCapacities->setUser($this);
        }

        $this->userCapacities = $userCapacities;

        return $this;
    }

    public function getUserBoosters(): Collection
    {
        return $this->userBoosters;
    }

    public function addUserBooster(UserBooster $userBooster): static
    {
        if (!$this->userBoosters->contains($userBooster)) {
            $this->userBoosters->add($userBooster);
            $userBooster->setUser($this);
        }

        return $this;
    }

    public function removeUserBooster(UserBooster $userBooster): static
    {
        if ($this->userBoosters->removeElement($userBooster)) {
            if ($userBooster->getUser() === $this) {
                $userBooster->setUser(null);
            }
        }

        return $this;
    }

    public function getUserAvailableBoosters(): Collection
    {
        return $this->userAvailableBoosters;
    }

    public function addUserAvailableBooster(UserAvailableBooster $userAvailableBooster): static
    {
        if (!$this->userAvailableBoosters->contains($userAvailableBooster)) {
            $this->userAvailableBoosters->add($userAvailableBooster);
            $userAvailableBooster->setUser($this);
        }

        return $this;
    }

    public function removeUserAvailableBooster(UserAvailableBooster $userAvailableBooster): static
    {
        if ($this->userAvailableBoosters->removeElement($userAvailableBooster)) {
            if ($userAvailableBooster->getUser() === $this) {
                $userAvailableBooster->setUser(null);
            }
        }

        return $this;
    }

    public function getShipMembers(): Collection
    {
        return $this->shipMembers;
    }

    public function addShipMember(ShipMember $shipMember): static
    {
        if (!$this->shipMembers->contains($shipMember)) {
            $this->shipMembers->add($shipMember);
            $shipMember->setUser($this);
        }

        return $this;
    }

    public function removeShipMember(ShipMember $shipMember): static
    {
        if ($this->shipMembers->removeElement($shipMember)) {
            if ($shipMember->getUser() === $this) {
                $shipMember->setUser(null);
            }
        }

        return $this;
    }

    public function getShip(): ?ShipMember
    {
        foreach ($this->shipMembers as $member) {
            if ($member->getShip() !== null) {
                return $member;
            }
        }

        return null;
    }

    public function getUserStatistics(): ?UserStatistics
    {
        return $this->userStatistics;
    }

    public function setUserStatistics(?UserStatistics $userStatistics): static
    {
        if ($userStatistics && $userStatistics->getUser() !== $this) {
            $userStatistics->setUser($this);
        }
        $this->userStatistics = $userStatistics;

        return $this;
    }

    public function getUserQuests(): Collection
    {
        return $this->userQuests;
    }

    public function addUserQuest(UserQuest $userQuest): static
    {
        if (!$this->userQuests->contains($userQuest)) {
            $this->userQuests->add($userQuest);
            $userQuest->setUser($this);
        }

        return $this;
    }

    public function removeUserQuest(UserQuest $userQuest): static
    {
        if ($this->userQuests->removeElement($userQuest)) {
            if ($userQuest->getUser() === $this) {
                $userQuest->setUser(null);
            }
        }

        return $this;
    }

    public function getUserRefills(): Collection
    {
        return $this->userRefills;
    }

    public function addUserRefill(UserRefill $userRefill): static
    {
        if (!$this->userRefills->contains($userRefill)) {
            $this->userRefills->add($userRefill);
            $userRefill->setUser($this);
        }

        return $this;
    }

    public function removeUserRefill(UserRefill $userRefill): static
    {
        if ($this->userRefills->removeElement($userRefill)) {
            if ($userRefill->getUser() === $this) {
                $userRefill->setUser(null);
            }
        }

        return $this;
    }

    public function getUserCoupons(): Collection
    {
        return $this->userCoupons;
    }

    public function addUserCoupon(UserCoupon $userCoupon): static
    {
        if (!$this->userCoupons->contains($userCoupon)) {
            $this->userCoupons->add($userCoupon);
            $userCoupon->setUser($this);
        }

        return $this;
    }

    public function removeUserCoupon(UserCoupon $userCoupon): static
    {
        if ($this->userCoupons->removeElement($userCoupon)) {
            if ($userCoupon->getUser() === $this) {
                $userCoupon->setUser(null);
            }
        }

        return $this;
    }

    public function getDailyRewardProgress(): ?UserDailyReward
    {
        return $this->dailyRewardProgress;
    }

    public function setDailyRewardProgress(?UserDailyReward $dailyRewardProgress): static
    {
        if ($dailyRewardProgress === null && $this->dailyRewardProgress !== null) {
            $this->dailyRewardProgress->setUser(null);
        }

        if ($dailyRewardProgress !== null && $dailyRewardProgress->getUser() !== $this) {
            $dailyRewardProgress->setUser($this);
        }

        $this->dailyRewardProgress = $dailyRewardProgress;

        return $this;
    }

    public function getEquippedTitle(): ?PlayerTitle
    {
        return $this->equippedTitle;
    }

    public function setEquippedTitle(?PlayerTitle $equippedTitle): static
    {
        $this->equippedTitle = $equippedTitle;

        return $this;
    }
}
