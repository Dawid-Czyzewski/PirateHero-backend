<?php

declare(strict_types=1);

namespace App\Service\MiniGames;

use App\Domain\Constants\TavernDiceConstants;
use App\Dto\MiniGames\TavernDicePlayResult;
use App\Entity\User;
use App\Exception\BusinessRuleException;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;

class TavernDiceService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private TavernDiceRandomInterface $random,
    ) {
    }

    public function play(User $user, int $stake): TavernDicePlayResult
    {
        if ($stake < TavernDiceConstants::MIN_STAKE || $stake > TavernDiceConstants::MAX_STAKE) {
            throw new BusinessRuleException('tavernDiceStakeInvalid');
        }

        $connection = $this->entityManager->getConnection();
        $connection->beginTransaction();

        try {
            $locked = $this->entityManager->find(User::class, $user->getId(), LockMode::PESSIMISTIC_WRITE);
            if (!$locked instanceof User) {
                throw new \RuntimeException('tavernDiceUserMissing');
            }

            $locked->spendGold($stake);

            $playerRoll = $this->random->roll2d6();
            $dealerRoll = $this->random->roll2d6();

            $tied = $playerRoll === $dealerRoll;
            $won = $playerRoll > $dealerRoll;

            if ($won) {
                $payoutGold = TavernDiceConstants::payout($stake);
                $locked->addGold($payoutGold);
            } elseif ($tied) {
                $payoutGold = $stake;
                $locked->addGold($stake);
            } else {
                $payoutGold = 0;
            }

            $goldAfter = (int) $locked->getGold();

            $this->entityManager->flush();
            $connection->commit();

            return new TavernDicePlayResult($won, $tied, $playerRoll, $dealerRoll, $goldAfter, $payoutGold);
        } catch (\Throwable $e) {
            if ($connection->isTransactionActive()) {
                $connection->rollBack();
            }
            throw $e;
        }
    }
}
