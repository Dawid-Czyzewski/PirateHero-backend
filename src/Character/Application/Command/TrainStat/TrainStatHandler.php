<?php

declare(strict_types=1);

namespace App\Character\Application\Command\TrainStat;

use App\Character\Domain\Exception\CharacterNotFound;
use App\Character\Domain\Exception\InsufficientStamina;
use App\Character\Domain\Repository\CharacterRepository;
use App\Shared\Domain\Model\UserId;
use Psr\Clock\ClockInterface;

final readonly class TrainStatHandler
{
    public function __construct(
        private CharacterRepository $characters,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws CharacterNotFound
     * @throws InsufficientStamina
     */
    public function __invoke(TrainStatCommand $command): void
    {
        $userId = UserId::fromString($command->userId);
        $character = $this->characters->ofUserId($userId) ?? throw CharacterNotFound::forUser($userId);

        $character->train($command->stat, $this->clock->now());
        $this->characters->save($character);
    }
}
