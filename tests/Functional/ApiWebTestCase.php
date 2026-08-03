<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Level;
use App\Entity\Mission;
use App\Entity\Training;
use App\Entity\User;
use App\Entity\UserActualActivity;
use App\Entity\UserBaseStatistics;
use App\Entity\Work;
use App\Enum\UserStatType;
use App\Service\Economy\StorageService;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

abstract class ApiWebTestCase extends WebTestCase
{
    protected static function ensureTestClient(): KernelBrowser
    {
        if (static::$booted) {
            $client = static::getClient();
            if ($client instanceof KernelBrowser) {
                return $client;
            }

            static::ensureKernelShutdown();
        }

        return static::createClient();
    }

    protected function createAuthenticatedClient(?User $user = null, string $plainPassword = 'Test_SecurePass_9'): KernelBrowser
    {
        $client = static::ensureTestClient();

        if ($user === null) {
            $user = $this->makePersistedActivatedUser($plainPassword);
        }
        $token = static::getContainer()->get(JWTTokenManagerInterface::class)->create($user);
        $client->setServerParameter('HTTP_AUTHORIZATION', 'Bearer '.$token);

        return $client;
    }

    protected function makePersistedActivatedUser(string $plainPassword = 'Test_SecurePass_9'): User
    {
        return $this->makePersistedUser($plainPassword, true);
    }

    protected function ensureUserBaseStatistics(User $user, int $statValue = 500): UserBaseStatistics
    {
        $em = $this->entityManager();
        $stats = $user->getUserBaseStatistics();
        if ($stats === null) {
            $stats = new UserBaseStatistics();
            $stats->setUser($user);
            $user->setUserBaseStatistics($stats);
        }

        $stats->setStrength($statValue);
        $stats->setAgility($statValue);
        $stats->setEndurance($statValue);
        $stats->setIntelligence($statValue);
        $stats->setLuck($statValue);

        $em->persist($stats);
        $em->persist($user);
        $em->flush();

        return $stats;
    }

    protected function ensureUserStorage(User $user): void
    {
        if ($user->getStorage() !== null) {
            return;
        }

        $em = $this->entityManager();
        static::ensureTestClient();
        $storageService = static::getContainer()->get(StorageService::class);
        $storage = $storageService->createEmptyStorageForUser($user);
        $user->setStorage($storage);
        $em->persist($storage);
        $em->persist($user);
        $em->flush();
    }

    protected function makePersistedUnactivatedUser(string $plainPassword = 'Test_SecurePass_9'): User
    {
        return $this->makePersistedUser($plainPassword, false);
    }

    protected function entityManager(): EntityManagerInterface
    {
        static::ensureTestClient();

        $registry = static::getContainer()->get('doctrine');
        \assert(method_exists($registry, 'getManager'));

        return $registry->getManager();
    }

    protected function assertJsonEnvelopeSuccess(Response $response): array
    {
        self::assertTrue($response->isSuccessful(), $response->getContent());
        $decoded = json_decode($response->getContent(), true);
        self::assertIsArray($decoded);
        self::assertArrayHasKey('data', $decoded);
        self::assertArrayHasKey('meta', $decoded);

        return $decoded;
    }

    /**
     * @return array<string, mixed>
     */
    protected function assertJsonEnvelopeData(Response $response): array
    {
        $decoded = $this->assertJsonEnvelopeSuccess($response);
        self::assertIsArray($decoded['data']);

        return $decoded['data'];
    }

    protected function assertProblemJson(Response $response): array
    {
        self::assertStringContainsString('application/problem+json', (string) $response->headers->get('Content-Type'));
        $decoded = json_decode($response->getContent(), true);
        self::assertIsArray($decoded);
        foreach (['type', 'title', 'status', 'detail'] as $k) {
            self::assertArrayHasKey($k, $decoded);
        }

        return $decoded;
    }

    protected function isPublicApiPath(string $path): bool
    {
        if (str_starts_with($path, '/api/login')) {
            return true;
        }
        if (str_starts_with($path, '/api/register')) {
            return true;
        }
        if (str_starts_with($path, '/api/token/refresh')) {
            return true;
        }
        if (preg_match('#^/api/activate-account/#', $path) === 1) {
            return true;
        }
        if (str_starts_with($path, '/api/password-reset/')) {
            return true;
        }

        return false;
    }

    protected function interpolatePath(string $path): string
    {
        return (string) preg_replace_callback('/\{([^}]+)\}/', static function (array $m) use ($path): string {
            $p = strtolower($m[1]);
            if ($p === '_format') {
                return 'json';
            }
            if (str_contains($p, 'token')) {
                return 'deadbeefcafe';
            }
            if (str_contains($p, 'slot')) {
                return '1';
            }
            if (str_contains($p, 'fight')) {
                return '1';
            }
            if ($p === 'id' && str_contains($path, '/ships/')) {
                return '1';
            }
            if ($p === 'id' && str_contains($path, '/user_quests/')) {
                return '1';
            }
            if ($p === 'id' && (str_contains($path, '/missions/') || str_contains($path, '/works/') || str_contains($path, '/trainings/'))) {
                return '999999';
            }

            return 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11';
        }, $path);
    }

    protected function makeMissionForUser(User $user, array $overrides = []): Mission
    {
        $mission = new Mission();
        $mission->setTitle($overrides['title'] ?? 'mission.test');
        $mission->setGoldReward($overrides['goldReward'] ?? 100);
        $mission->setExpReward($overrides['expReward'] ?? 150);
        $mission->setDurationInSeconds($overrides['durationInSeconds'] ?? 300);
        $mission->setEnergyCost($overrides['energyCost'] ?? 10);
        $mission->setUser($user);

        $em = $this->entityManager();
        $em->persist($mission);
        $em->flush();

        return $mission;
    }

    protected function setMissionActivity(User $user, Mission $mission, \DateTimeInterface $startTime): UserActualActivity
    {
        $activity = new UserActualActivity();
        $activity->setUser($user);
        $activity->setMission($mission);
        $activity->setStartTime($startTime);

        $user->setCurrentActivity($activity);

        $em = $this->entityManager();
        $em->persist($activity);
        $em->persist($user);
        $em->flush();

        return $activity;
    }

    protected function makeWorkForUser(User $user, array $overrides = []): Work
    {
        $work = new Work();
        $work->setTitle($overrides['title'] ?? 'work.test');
        $work->setHoursCount($overrides['hoursCount'] ?? 1);
        $work->setBaseGold($overrides['baseGold'] ?? 50);
        $work->setUser($user);

        $em = $this->entityManager();
        $em->persist($work);
        $em->flush();

        return $work;
    }

    protected function setWorkActivity(User $user, Work $work, \DateTimeInterface $startTime): UserActualActivity
    {
        $activity = new UserActualActivity();
        $activity->setUser($user);
        $activity->setWork($work);
        $activity->setStartTime($startTime);

        $user->setCurrentActivity($activity);

        $em = $this->entityManager();
        $em->persist($activity);
        $em->persist($user);
        $em->flush();

        return $activity;
    }

    protected function makeTrainingForUser(User $user, array $overrides = []): Training
    {
        $training = new Training();
        $training->setTitle($overrides['title'] ?? 'training.test');
        $training->setDescription($overrides['description'] ?? 'training.test.description');
        $training->setDurationInSeconds($overrides['durationInSeconds'] ?? 600);
        $training->setTrainingPointsCost($overrides['trainingPointsCost'] ?? 2);
        $training->setSkillPointsReward($overrides['skillPointsReward'] ?? 1);
        $training->setStatType($overrides['statType'] ?? UserStatType::STRENGTH);
        $training->setUser($user);

        $em = $this->entityManager();
        $em->persist($training);
        $em->flush();

        return $training;
    }

    protected function setTrainingActivity(User $user, Training $training, \DateTimeInterface $startTime): UserActualActivity
    {
        $activity = new UserActualActivity();
        $activity->setUser($user);
        $activity->setTraining($training);
        $activity->setStartTime($startTime);

        $user->setCurrentActivity($activity);

        $em = $this->entityManager();
        $em->persist($activity);
        $em->persist($user);
        $em->flush();

        return $activity;
    }

    private function makePersistedUser(string $plainPassword, bool $activated): User
    {
        $em = $this->entityManager();
        $level = $em->getRepository(Level::class)->findOneBy(['name' => '1']);
        if ($level === null) {
            $level = new Level();
            $level->setName('1');
            $level->setExpToNextLevel(220);
            $em->persist($level);
            $em->flush();
        }

        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $user = new User();
        $user->setEmail(sprintf('player_%s@test.local', bin2hex(random_bytes(5))));
        $user->setUsername(sprintf('u_%s', bin2hex(random_bytes(3))));
        $user->setPassword($hasher->hashPassword($user, $plainPassword));
        $user->setLevel($level);
        if ($activated) {
            $user->setActivateToken(null);
        }
        $user->setGold(100_000);
        $user->setDiamonds(10_000);
        $user->setTrainingPoints(500);
        $user->setDuelPoints(50);
        $user->setFamePoints(100);
        $user->setEnergyPoints(100);
        $em->persist($user);
        $em->flush();

        return $user;
    }
}
