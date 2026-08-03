<?php

declare(strict_types=1);

namespace App\Service\User;

use App\Entity\Mission;
use App\Entity\Ship;
use App\Entity\User;
use App\Entity\Work;
use App\Service\Economy\BoosterService;
use App\Service\Economy\UserStoreService;
use App\Service\GameShop\GameShopService;
use App\Service\Progression\MissionService;
use App\Service\Progression\TitleService;
use App\Service\Progression\WorkService;
use App\Service\Ship\ShipInvitationService;
use App\Service\Ship\ShipMembershipService;
use App\Service\ShopBoosters\ShopBoosterSessionService;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class UserProfileAssembler
{
    public function __construct(
        private readonly NormalizerInterface $serializer,
        private readonly ShipMembershipService $shipMembershipService,
        private readonly ShipInvitationService $shipInvitationService,
        private readonly MissionService $missionService,
        private readonly WorkService $workService,
        private readonly UserStoreService $userStoreService,
        private readonly GameShopService $gameShopService,
        private readonly ShopBoosterSessionService $shopBoosterSessionService,
        private readonly TitleService $titleService,
        private readonly LevelService $levelService,
        private readonly BoosterService $boosterService,
    ) {
    }

    /**
     * Serializer-backed profile payload with missions, works, shop state, and ship bonuses.
     *
     * @return array<string, mixed>
     */
    public function assembleUserData(User $user): array
    {
        $this->levelService->checkAndUpdateLevel($user);
        $this->boosterService->cleanupExpiredBoostersAndGenerateNew($user);
        $this->boosterService->calculateActualCapacity($user);

        /** @var array<string, mixed> $userData */
        $userData = $this->serializer->normalize($user, 'json', ['groups' => ['user:read']]);

        $ship = $this->shipMembershipService->getShipForUser($user);
        $userData['shipBonuses'] = $this->buildShipBonuses($ship);

        $missions = $user->getMissions()->toArray();
        $userData['missions'] = array_map(
            fn (Mission $mission) => $this->missionService->formatMissionForUser($user, $mission, $ship),
            $missions
        );

        $works = $user->getWorks()->toArray();
        $userData['works'] = array_map(
            fn (Work $work) => $this->workService->formatWorkForUser($user, $work, $ship),
            $works
        );

        $currentActivity = $user->getCurrentActivity();
        if ($currentActivity) {
            if ($currentActivity->getMission()) {
                $userData['currentActivity']['mission'] = $this->missionService->formatMissionForUser(
                    $user,
                    $currentActivity->getMission(),
                    $ship
                );
            }
            if ($currentActivity->getWork()) {
                $userData['currentActivity']['work'] = $this->workService->formatWorkForUser(
                    $user,
                    $currentActivity->getWork(),
                    $ship
                );
            }
        }

        $this->userStoreService->ensureUserStore($user);
        $userData['gameShop'] = $this->gameShopService->buildState($user);
        $this->shopBoosterSessionService->pruneExpiredSessions($user);
        $userData['sessionShopBoosters'] = $this->shopBoosterSessionService->buildActiveEntriesForUser($user);

        $userData['hasShip'] = $ship !== null;
        $userData['equippedTitle'] = $this->titleService->buildEquippedTitleDto($user->getEquippedTitle());

        return $userData;
    }

    /**
     * @return array{
     *     id: int|string,
     *     username: string,
     *     avatarName: string|null,
     *     famePoints: int,
     *     level: array{id: int|string, name: string}|null,
     *     userBaseStatistics: array{
     *         endurance: int,
     *         strength: int,
     *         agility: int,
     *         intelligence: int,
     *         luck: int
     *     }|null,
     *     userEquipment: array{
     *         userEquipmentSlots: list<array{
     *             slotType: string|null,
     *             wearableItem: array{
     *                 id: int|string,
     *                 name: string|null,
     *                 type: string|null,
     *                 rarity: string|null,
     *                 statistics: array{
     *                     strongPoints: int,
     *                     agilityPoints: int,
     *                     criticalChancePoints: int,
     *                     intelligencePoints: int,
     *                     healthPoints: int
     *                 }|null
     *             }|null
     *         }>
     *     }|null,
     *     ship: array{
     *         id: int|string,
     *         title: string,
     *         membersCount: int,
     *         maxMembers: int,
     *         hullUpgrade: int,
     *         famePoints: int
     *     }|null,
     *     hasInvitation: bool,
     *     shipBonuses?: array{
     *         skills: array{level: int, percent: int, multiplier: float},
     *         missions: array{level: int, percent: int, multiplier: float},
     *         work: array{level: int, percent: int, multiplier: float}
     *     },
     *     sessionShopBoosters: list<array{boosterId: string, expiresAt: int}>,
     *     equippedTitle: array{code: string, nameKey: string}|null
     * }
     */
    public function assembleUserPreview(User $subject, User $viewer): array
    {
        $ship = $this->shipMembershipService->getShipForUser($subject);
        $hasInvitation = false;

        $viewerShip = $this->shipMembershipService->getShipForUser($viewer);
        if ($viewerShip) {
            $invitation = $this->shipInvitationService->getInvitationForUser($viewerShip, $subject);
            $hasInvitation = $invitation !== null;
        }

        $previewData = [
            'id' => $subject->getId(),
            'username' => $subject->getUsername(),
            'avatarName' => $subject->getAvatarName(),
            'famePoints' => $subject->getFamePoints() ?? 0,
            'level' => $subject->getLevel() ? [
                'id' => $subject->getLevel()->getId(),
                'name' => $subject->getLevel()->getName(),
            ] : null,
            'userBaseStatistics' => $subject->getUserBaseStatistics() ? [
                'endurance' => $subject->getUserBaseStatistics()->getEndurance(),
                'strength' => $subject->getUserBaseStatistics()->getStrength(),
                'agility' => $subject->getUserBaseStatistics()->getAgility(),
                'intelligence' => $subject->getUserBaseStatistics()->getIntelligence(),
                'luck' => $subject->getUserBaseStatistics()->getLuck(),
            ] : null,
            'userEquipment' => null,
            'ship' => null,
            'hasInvitation' => $hasInvitation,
        ];

        $userEquipment = $subject->getUserEquipment();
        if ($userEquipment) {
            $equipmentSlots = [];
            foreach ($userEquipment->getUserEquipmentSlots() as $slot) {
                $item = $slot->getWearableItem();
                $slotData = [
                    'slotType' => $slot->getType()?->value,
                    'wearableItem' => null,
                ];

                if ($item) {
                    $slotData['wearableItem'] = [
                        'id' => $item->getId(),
                        'name' => $item->getName(),
                        'type' => $item->getType()?->value,
                        'rarity' => $item->getRarity()?->value,
                        'statistics' => $item->getStatistics()?->toClientArray(),
                    ];
                }

                $equipmentSlots[] = $slotData;
            }
            $previewData['userEquipment'] = [
                'userEquipmentSlots' => $equipmentSlots,
            ];
        }

        if ($ship) {
            $previewData['ship'] = [
                'id' => $ship->getId(),
                'title' => $ship->getTitle(),
                'membersCount' => $ship->getMembers()->count(),
                'maxMembers' => $ship->getMaxMembers(),
                'hullUpgrade' => $ship->getHullUpgrade(),
                'famePoints' => $ship->getFamePoints(),
            ];
            $previewData['shipBonuses'] = $this->buildShipBonuses($ship);
        }

        $this->shopBoosterSessionService->pruneExpiredSessions($subject);
        $previewData['sessionShopBoosters'] = $this->shopBoosterSessionService->buildActiveEntriesForUser($subject);
        $previewData['equippedTitle'] = $this->titleService->buildEquippedTitleDto($subject->getEquippedTitle());

        return $previewData;
    }

    /**
     * @return array{
     *   skills: array{level: int, percent: int, multiplier: float},
     *   missions: array{level: int, percent: int, multiplier: float},
     *   work: array{level: int, percent: int, multiplier: float}
     * }
     */
    public function buildShipBonuses(?Ship $ship): array
    {
        $skillsLevel = $ship?->getSkillsUpgrade() ?? 0;
        $missionsLevel = $ship?->getMissionsUpgrade() ?? 0;
        $workLevel = $ship?->getWorkUpgrade() ?? 0;

        return [
            'skills' => [
                'level' => $skillsLevel,
                'percent' => $skillsLevel,
                'multiplier' => round(1 + ($skillsLevel / 100), 2),
            ],
            'missions' => [
                'level' => $missionsLevel,
                'percent' => $missionsLevel,
                'multiplier' => round(1 + ($missionsLevel / 100), 2),
            ],
            'work' => [
                'level' => $workLevel,
                'percent' => $workLevel,
                'multiplier' => round(1 + ($workLevel / 100), 2),
            ],
        ];
    }
}
