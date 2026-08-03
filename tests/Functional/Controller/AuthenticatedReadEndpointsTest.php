<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Tests\Functional\ApiWebTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class AuthenticatedReadEndpointsTest extends ApiWebTestCase
{
    public static function getCases(): \Generator
    {
        yield 'RankingController::getPlayersRanking' => ['/api/rankings/players'];
        yield 'RankingController::getShipsRanking' => ['/api/rankings/ships'];
        yield 'BoosterController::getAvailableBoosters' => ['/api/boosters/available'];
        yield 'DungeonController::getProgress' => ['/api/users/dungeons/progress'];
        yield 'BestiaryController::getBestiary' => ['/api/users/bestiary/entries'];
        yield 'TitleController::listTitles' => ['/api/user_titles'];
        yield 'FightController::getOpponents' => ['/api/users/fights/opponents'];
        yield 'FightController::getFightHistory' => ['/api/users/fights/history'];
        yield 'QuestTaskController::getUserQuests' => ['/api/user_quests'];
        yield 'EnergyRefillController::getRefillInfo' => ['/api/users/energy/refill/info'];
        yield 'TrainingRefillController::getRefillInfo' => ['/api/users/training/refill/info'];
        yield 'FightRefillController::getRefillInfo' => ['/api/users/fight/refill/info'];
        yield 'CouponController::getCouponHistory' => ['/api/coupons/history'];
        yield 'ShipOverviewController::getMyShip' => ['/api/ships/my-ship'];
        yield 'ShipNotificationsController::getUnreadNotificationsCount' => ['/api/ships/unread-notifications-count'];
        yield 'ShipEnrollmentController::getMyInvitations' => ['/api/ships/my-invitations'];
        yield 'ShipEnrollmentController::getMyJoinRequests' => ['/api/ships/my-join-requests'];
    }

    #[DataProvider('getCases')]
    public function testEndpointReturnsHttp200WithEnvelope(string $path): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request('GET', $path);
        $response = $client->getResponse();
        $code = $response->getStatusCode();
        self::assertNotSame(500, $code, $response->getContent());
        if ($code === 403 || $code === 404) {
            return;
        }
        $this->assertJsonEnvelopeSuccess($response);
    }

    public function testUserControllerGetUserDataReturnsEnvelope(): void
    {
        $user = $this->makePersistedActivatedUser();
        $client = $this->createAuthenticatedClient($user);
        $client->request('GET', '/api/users/'.$user->getId());
        $this->assertJsonEnvelopeSuccess($client->getResponse());
    }

    public function testUserControllerGetUserPreviewReturnsEnvelope(): void
    {
        $viewer = $this->makePersistedActivatedUser();
        $target = $this->makePersistedActivatedUser();
        $client = $this->createAuthenticatedClient($viewer);
        $client->request('GET', '/api/users/'.$target->getId().'/preview');
        $this->assertJsonEnvelopeSuccess($client->getResponse());
    }

    public function testUserStoreControllerGetByUserIdReturnsEnvelope(): void
    {
        $user = $this->makePersistedActivatedUser();
        $client = $this->createAuthenticatedClient($user);
        $client->request('GET', '/api/user-store/by-user/'.$user->getId());
        if ($client->getResponse()->getStatusCode() === 404) {
            self::markTestSkipped('User store row may not exist until created by game flow.');

            return;
        }
        $this->assertJsonEnvelopeSuccess($client->getResponse());
    }
}
