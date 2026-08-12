<?php

declare(strict_types=1);

namespace App\ApiResource;

use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Post;
use App\Controller\BestiaryController;
use App\Controller\BoosterController;
use App\Controller\DailyChallengeController;
use App\Controller\DailyRewardController;
use App\Controller\DungeonController;
use App\Controller\FightController;
use App\Controller\PremiumShopController;
use App\Controller\RankingController;
use App\Controller\UserActivateAccountController;
use App\Controller\UserController;
use App\Controller\UserRegisterController;
use App\Controller\UserSkillPointsController;
use App\Dto\UserRegisterDto;
use App\Entity\User;


#[ApiResource(
    shortName: 'User',
    operations: [
        new Post(
            uriTemplate: '/register',
            input: UserRegisterDto::class,
            controller: UserRegisterController::class,
        ),
        new Get(
            uriTemplate: '/activate-account/{token}',
            controller: UserActivateAccountController::class,
            read: false,
            uriVariables: [
                'token' => new Link(
                    parameterName: 'token'
                ),
            ]
        ),
        new Get(
            uriTemplate: '/users/{id}',
            controller: UserController::class.'::getUserData',
            security: "is_granted('IS_AUTHENTICATED_FULLY') and (is_granted('ROLE_ADMIN') or object.getId() == user.getId())",
            securityMessage: 'Access denied. You can only access your own data unless you are an admin.',
            normalizationContext: ['groups' => ['user:read'], 'force_eager' => true]
        ),
        new Get(
            uriTemplate: '/users/{id}/preview',
            controller: UserController::class.'::getUserPreview',
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            securityMessage: 'Access denied. You must be authenticated to view user previews.',
        ),
        new Post(
            uriTemplate: '/users/{id}/add-skill-point',
            controller: UserSkillPointsController::class,
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
        ),
        new Get(
            uriTemplate: '/users/fights/opponents',
            controller: FightController::class.'::getOpponents',
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
        ),
        new Post(
            uriTemplate: '/users/fights/start',
            controller: FightController::class.'::startFight',
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
        ),
        new Get(
            uriTemplate: '/users/fights/history',
            controller: FightController::class.'::getFightHistory',
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
        ),
        new Get(
            uriTemplate: '/users/fights/replay/{fightId}',
            controller: FightController::class.'::getFightReplay',
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            read: false,
        ),
        new Get(
            uriTemplate: '/users/dungeons/progress',
            controller: DungeonController::class.'::getProgress',
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            read: false,
        ),
        new Post(
            uriTemplate: '/users/dungeons/fight',
            controller: DungeonController::class.'::fightStage',
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            read: false,
        ),
        new Get(
            uriTemplate: '/users/bestiary/entries',
            controller: BestiaryController::class.'::getBestiary',
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            read: false,
        ),
        new Get(
            uriTemplate: '/boosters/available',
            controller: BoosterController::class.'::getAvailableBoosters',
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
        ),
        new Post(
            uriTemplate: '/boosters/buy',
            controller: BoosterController::class.'::buyBooster',
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
        ),
        new Post(
            uriTemplate: '/boosters/use',
            controller: BoosterController::class.'::useBooster',
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
        ),
        new Get(
            uriTemplate: '/rankings/players',
            controller: RankingController::class.'::getPlayersRanking',
            name: 'api_rankings_players',
            read: false
        ),
        new Get(
            uriTemplate: '/users/daily-rewards/status',
            controller: DailyRewardController::class.'::getStatus',
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            read: false,
        ),
        new Post(
            uriTemplate: '/users/daily-rewards/claim',
            controller: DailyRewardController::class.'::claim',
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            read: false,
        ),
        new Get(
            uriTemplate: '/users/daily-challenges',
            controller: DailyChallengeController::class.'::getStatus',
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            read: false,
        ),
        new Post(
            uriTemplate: '/users/daily-challenges/claim',
            controller: DailyChallengeController::class.'::claimSlot',
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            read: false,
        ),
        new Post(
            uriTemplate: '/users/daily-challenges/claim-bonus',
            controller: DailyChallengeController::class.'::claimBonus',
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            read: false,
        ),
        new Get(
            uriTemplate: '/users/premium-shop/catalog',
            controller: PremiumShopController::class.'::getCatalog',
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            read: false,
        ),
        new Get(
            uriTemplate: '/users/premium-shop/transactions',
            controller: PremiumShopController::class.'::getTransactions',
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            read: false,
        ),
        new Post(
            uriTemplate: '/users/premium-shop/purchase',
            controller: PremiumShopController::class.'::purchase',
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            read: false,
        ),
    ],
    stateOptions: new Options(entityClass: User::class),
)]
final class UserApi
{
}
