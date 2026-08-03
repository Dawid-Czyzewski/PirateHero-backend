<?php

declare(strict_types=1);

namespace App\Controller\Ship;

use App\Dto\Ship\SendShipMessageInput;
use App\Entity\User;
use App\Http\ApiEnvelope;
use App\Service\Ship\ShipChatService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[AsController]
final class ShipChatController extends AbstractController
{
    public function __construct(
        private ShipChatService $shipChatService,
        private ShipHttpHelper $shipHttp,
    ) {
    }

    public function sendMessage(#[CurrentUser] User $user, #[MapRequestPayload] SendShipMessageInput $input): JsonResponse
    {
        $ship = $this->shipHttp->requireCurrentUserShip($user);
        $message = $this->shipChatService->addMessage($ship, $user, trim($input->content));

        return ApiEnvelope::jsonResponse([
            'shipMessage' => ShipHttpHelper::shipMessageToArray($message),
        ], 'shipMessageSent', 201);
    }

    public function getMessages(#[CurrentUser] User $user): JsonResponse
    {
        $ship = $this->shipHttp->requireCurrentUserShip($user);
        $messages = $this->shipChatService->getMessages($ship, 50);
        $messagesData = [];
        foreach ($messages as $msg) {
            $messagesData[] = ShipHttpHelper::shipMessageToArray($msg);
        }

        return ApiEnvelope::jsonResponse(['messages' => $messagesData], null);
    }
}
