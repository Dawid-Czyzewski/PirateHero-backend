<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\User;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\Economy\BoosterService;
use App\Service\Economy\UserStoreService;
use App\Service\GameShop\GameShopItemViewNormalizer;
use App\Service\GameShop\GameShopService;
use App\Service\Progression\MissionService;
use App\Service\Progression\TitleService;
use App\Service\Progression\WorkService;
use App\Service\Ship\ShipInvitationService;
use App\Service\Ship\ShipMembershipService;
use App\Service\ShopBoosters\ShopBoosterSessionService;
use App\Service\User\LevelService;
use App\Service\User\UserProfileAssembler;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class UserProfileAssemblerTest extends TestCase
{
    public function testAssembleUserPreviewContainsExpectedKeys(): void
    {
        $subject = $this->createConfiguredMock(User::class, [
            'getId' => '42',
            'getUsername' => 'hero',
            'getAvatarName' => 'avatar_1',
            'getFamePoints' => 100,
            'getLevel' => null,
            'getUserBaseStatistics' => null,
            'getUserEquipment' => null,
            'getEquippedTitle' => null,
        ]);

        $viewer = $this->createConfiguredMock(User::class, ['getId' => '7']);

        $shipMembership = $this->createMock(ShipMembershipService::class);
        $shipMembership->method('getShipForUser')->willReturn(null);

        $shipInvitation = $this->createMock(ShipInvitationService::class);

        $shopBoosters = $this->createMock(ShopBoosterSessionService::class);
        $shopBoosters->method('pruneExpiredSessions');
        $shopBoosters->method('buildActiveEntriesForUser')->willReturn([]);

        $titleService = $this->createMock(TitleService::class);
        $titleService->method('buildEquippedTitleDto')->willReturn(null);

        $levelService = $this->createMock(LevelService::class);
        $levelService->method('checkAndUpdateLevel');

        $boosterService = $this->createMock(BoosterService::class);
        $boosterService->method('cleanupExpiredBoostersAndGenerateNew');
        $boosterService->method('calculateActualCapacity');

        $assembler = new UserProfileAssembler(
            $this->createMock(NormalizerInterface::class),
            $shipMembership,
            $shipInvitation,
            $this->createMock(MissionService::class),
            $this->createMock(WorkService::class),
            $this->createMock(UserStoreService::class),
            new GameShopService(
                $this->createMock(\Doctrine\ORM\EntityManagerInterface::class),
                new GameShopItemViewNormalizer(),
                $this->createMock(UserRepository::class),
                $this->createMock(\App\Service\Progression\QuestService::class),
            ),
            $shopBoosters,
            $titleService,
            $levelService,
            $boosterService,
        );

        $preview = $assembler->assembleUserPreview($subject, $viewer);

        foreach ([
            'id',
            'username',
            'avatarName',
            'famePoints',
            'level',
            'userBaseStatistics',
            'userEquipment',
            'ship',
            'hasInvitation',
            'sessionShopBoosters',
            'equippedTitle',
        ] as $key) {
            self::assertArrayHasKey($key, $preview, "Missing preview key: {$key}");
        }

        self::assertSame('42', $preview['id']);
        self::assertFalse($preview['hasInvitation']);
    }
}
