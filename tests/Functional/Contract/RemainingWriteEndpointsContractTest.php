<?php

declare(strict_types=1);

namespace App\Tests\Functional\Contract;

use App\Tests\Functional\ApiWebTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class RemainingWriteEndpointsContractTest extends ApiWebTestCase
{
    public static function endpoints(): \Generator
    {
        yield 'energy_refill' => ['/api/users/energy/refill', null];
        yield 'training_refill' => ['/api/users/training/refill', null];
        yield 'fight_refill' => ['/api/users/fight/refill', null];
        yield 'coupon_redeem' => ['/api/coupons/redeem', json_encode([], \JSON_THROW_ON_ERROR)];
        yield 'coin_flip_play' => [
            '/api/games/coin-flip/play',
            json_encode(['stake' => 0, 'choice' => 'heads'], \JSON_THROW_ON_ERROR),
        ];
        yield 'account_change_password' => [
            '/api/account/change-password',
            json_encode([
                'currentPassword' => 'x',
                'newPassword' => 'y',
                'newPasswordRepeat' => 'z',
            ], \JSON_THROW_ON_ERROR),
        ];
        yield 'club_deposit' => ['/api/ships/deposit', json_encode([], \JSON_THROW_ON_ERROR)];
        yield 'club_upgrade' => ['/api/ships/upgrade', json_encode([], \JSON_THROW_ON_ERROR)];
        yield 'club_accept_invitation' => ['/api/ships/accept-invitation', json_encode(['invitationId' => 1], \JSON_THROW_ON_ERROR)];
        yield 'club_decline_invitation' => ['/api/ships/decline-invitation', json_encode(['invitationId' => 1], \JSON_THROW_ON_ERROR)];
        yield 'club_mark_invitation_read' => ['/api/ships/mark-invitation-read', json_encode(['invitationId' => 1], \JSON_THROW_ON_ERROR)];
        yield 'club_mark_join_request_read' => ['/api/ships/mark-join-request-read', json_encode(['requestId' => 1], \JSON_THROW_ON_ERROR)];
        yield 'club_mark_removal_read' => ['/api/ships/mark-removal-notification-read', json_encode(['notificationId' => 1], \JSON_THROW_ON_ERROR)];
        yield 'club_mark_fight_read' => ['/api/ships/mark-fight-notification-read', json_encode(['notificationId' => 1], \JSON_THROW_ON_ERROR)];
        yield 'clubs_fight_start' => ['/api/ships/fights/start', json_encode([], \JSON_THROW_ON_ERROR)];
        yield 'user_add_skill' => ['/api/users/a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11/add-skill-point', json_encode([], \JSON_THROW_ON_ERROR)];
        yield 'storage_move' => ['/api/storage/a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11/move-item/1/2', null];
        yield 'equip' => ['/api/user_equipments/a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11/equip', json_encode([], \JSON_THROW_ON_ERROR)];
        yield 'unequip' => ['/api/user_equipments/a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11/unequip', json_encode([], \JSON_THROW_ON_ERROR)];
        yield 'claim_quest_reward' => ['/api/user_quests/1/claim-reward', null];
        yield 'equip_title' => [
            '/api/user_titles/equip',
            json_encode(['titleCode' => 'rookie'], \JSON_THROW_ON_ERROR),
        ];
    }

    #[DataProvider('endpoints')]
    public function testEndpointReturnsControlledStatusAndNever500(string $path, ?string $body): void
    {
        $client = $this->createAuthenticatedClient();
        $server = $body !== null ? ['CONTENT_TYPE' => 'application/json'] : [];
        $client->request('POST', $path, [], [], $server, $body ?? '');

        $status = $client->getResponse()->getStatusCode();
        self::assertNotSame(500, $status, $path.' returned 500');
        self::assertContains($status, [200, 201, 400, 401, 403, 404, 405, 415, 422], $path.' returned unexpected status '.$status);
    }
}
