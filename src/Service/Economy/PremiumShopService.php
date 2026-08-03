<?php

declare(strict_types=1);

namespace App\Service\Economy;

use App\Config\PremiumShopCatalog;
use App\Entity\PremiumShopTransaction;
use App\Entity\User;
use App\Exception\BusinessRuleException;
use App\Exception\ResourceNotFoundException;
use App\Repository\PremiumShopTransactionRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;

class PremiumShopService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private PremiumShopTransactionRepository $transactionRepository,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getCatalog(): array
    {
        return [
            'packs' => PremiumShopCatalog::catalogPacks(),
        ];
    }

    /**
     * Mock purchase: credits diamonds without real payment processing.
     *
     * @return array<string, mixed>
     */
    public function purchase(User $user, string $packId): array
    {
        $pack = PremiumShopCatalog::findPack($packId);
        if ($pack === null) {
            throw new BusinessRuleException('premiumPackNotFound');
        }

        $diamonds = PremiumShopCatalog::totalDiamonds($pack);
        $connection = $this->entityManager->getConnection();
        $connection->beginTransaction();
        try {
            $lockedUser = $this->entityManager->find(User::class, $user->getId(), LockMode::PESSIMISTIC_WRITE);
            if ($lockedUser === null) {
                throw new ResourceNotFoundException('userNotFound');
            }

            $lockedUser->addDiamonds($diamonds);

            $transaction = new PremiumShopTransaction();
            $transaction->setUser($lockedUser);
            $transaction->setPackId($pack['id']);
            $transaction->setDiamonds($diamonds);
            $transaction->setPricePln(number_format($pack['pricePln'], 2, '.', ''));
            $transaction->setPurchasedAt(new \DateTimeImmutable());

            $this->entityManager->persist($transaction);
            $this->entityManager->persist($lockedUser);
            $this->entityManager->flush();
            $connection->commit();

            return [
                'transaction' => $this->serializeTransaction($transaction),
                'updatedUser' => [
                    'diamonds' => $lockedUser->getDiamonds(),
                ],
            ];
        } catch (\Throwable $e) {
            $connection->rollBack();
            throw $e;
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getTransactionHistory(User $user): array
    {
        $transactions = $this->transactionRepository->findRecentForUser($user);

        return array_map(fn (PremiumShopTransaction $tx) => $this->serializeTransaction($tx), $transactions);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeTransaction(PremiumShopTransaction $transaction): array
    {
        return [
            'id' => $transaction->getId(),
            'packId' => $transaction->getPackId(),
            'diamonds' => $transaction->getDiamonds(),
            'pricePln' => (float) $transaction->getPricePln(),
            'purchasedAt' => $transaction->getPurchasedAt()?->format(\DateTimeInterface::ATOM),
        ];
    }
}
