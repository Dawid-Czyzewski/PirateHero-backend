<?php

declare(strict_types=1);

namespace App\Service\MiniGames;

use App\Dto\MiniGames\CoinFlipPlayResult;
use App\Entity\User;
use App\Enum\CoinFlipSide;
use App\Exception\BusinessRuleException;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;

class CoinFlipService
{
    public const MIN_STAKE = 1;

    public const MAX_STAKE = 10;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private CoinFlipRandomInterface $random,
    ) {
    }

    public function play(User $user, int $stake, CoinFlipSide $playerChoice): CoinFlipPlayResult
    {
        if ($stake < self::MIN_STAKE || $stake > self::MAX_STAKE) {
            throw new BusinessRuleException('coinFlipStakeInvalid');
        }

        $connection = $this->entityManager->getConnection();
        $connection->beginTransaction();

        try {
            $locked = $this->entityManager->find(User::class, $user->getId(), LockMode::PESSIMISTIC_WRITE);
            if (!$locked instanceof User) {
                throw new \RuntimeException('coinFlipUserMissing');
            }

            $locked->spendDiamonds($stake);

            $outcome = $this->random->flip();
            $won = $outcome === $playerChoice;

            if ($won) {
                $locked->addDiamonds(2 * $stake);
            }

            $diamondsAfter = (int) $locked->getDiamonds();
            $payoutDiamonds = $won ? 2 * $stake : 0;

            $this->entityManager->flush();
            $connection->commit();

            return new CoinFlipPlayResult($won, $outcome, $diamondsAfter, $payoutDiamonds);
        } catch (\Throwable $e) {
            if ($connection->isTransactionActive()) {
                $connection->rollBack();
            }
            throw $e;
        }
    }
}
