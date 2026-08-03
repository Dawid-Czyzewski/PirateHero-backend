<?php

declare(strict_types=1);

namespace App\Service\Bestiary;

use App\Bestiary\BestiaryCatalog;
use App\Entity\User;
use App\Entity\UserBestiaryEntry;
use App\Repository\UserBestiaryEntryRepository;
use Doctrine\ORM\EntityManagerInterface;

readonly class BestiaryService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserBestiaryEntryRepository $bestiaryEntryRepository,
        private readonly bool $unlockAllForTesting = false,
    ) {
    }

    /**
     * @return array{entries: list<array{
     *     enemyId: string,
     *     dungeonId: string,
     *     stage: int,
     *     discovered: bool,
     *     defeatedAt: string|null,
     *     nameKey: string,
     *     loreKey: string
     * }>}
     */
    public function getForUser(User $user): array
    {
        $discovered = $this->bestiaryEntryRepository->getDiscoveredMapForUser($user);
        $entries = [];

        foreach (BestiaryCatalog::entries() as $catalogEntry) {
            $record = $discovered[$catalogEntry['enemyId']] ?? null;
            $isDiscovered = $this->unlockAllForTesting || $record instanceof UserBestiaryEntry;
            $defeatedAt = $record?->getDefeatedAt();
            if ($isDiscovered && $defeatedAt === null && $this->unlockAllForTesting) {
                $defeatedAt = new \DateTimeImmutable();
            }

            $entries[] = [
                'enemyId' => $catalogEntry['enemyId'],
                'dungeonId' => $catalogEntry['dungeonId'],
                'stage' => $catalogEntry['stage'],
                'discovered' => $isDiscovered,
                'defeatedAt' => $defeatedAt?->format(\DateTimeInterface::ATOM),
                'nameKey' => $catalogEntry['nameKey'],
                'loreKey' => $catalogEntry['loreKey'],
            ];
        }

        return ['entries' => $entries];
    }

    public function recordDefeat(User $user, string $dungeonId, int $stage): void
    {
        if (BestiaryCatalog::find($dungeonId, $stage) === null) {
            return;
        }

        $existing = $this->bestiaryEntryRepository->findOneForUserDungeonStage($user, $dungeonId, $stage);
        if ($existing instanceof UserBestiaryEntry) {
            return;
        }

        $entry = new UserBestiaryEntry();
        $entry->setUser($user);
        $entry->setDungeonId($dungeonId);
        $entry->setStage($stage);
        $entry->setDefeatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($entry);
    }
}
