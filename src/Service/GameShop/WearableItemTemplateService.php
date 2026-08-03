<?php

declare(strict_types=1);

namespace App\Service\GameShop;

use App\Config\WearableItemCatalog;
use App\Entity\WearableItem;
use App\Entity\WearableItemTemplate;
use App\Enum\WearableItemRarity;
use App\Enum\WearableItemType;
use App\Repository\WearableItemTemplateRepository;
use Doctrine\ORM\EntityManagerInterface;

readonly class WearableItemTemplateService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly WearableItemTemplateRepository $templateRepository,
        private readonly WearableVariantResolver $variantResolver,
        private readonly WearableTemplatePicker $templatePicker,
    ) {
    }

    /**
     * @return array{created: int, updated: int}
     */
    public function seedFromConfig(bool $purgeExisting = false): array
    {
        if ($purgeExisting) {
            $this->entityManager->createQuery('DELETE FROM App\Entity\WearableItemTemplate t')->execute();
            $this->entityManager->flush();
        }

        $created = 0;
        $updated = 0;

        foreach (WearableItemCatalog::entries() as $entry) {
            $existing = $this->templateRepository->findOneByPublicCode($entry['publicCode']);
            if ($existing instanceof WearableItemTemplate) {
                $this->fillTemplate($existing, $entry);
                ++$updated;
                continue;
            }

            $template = new WearableItemTemplate();
            $template->setPublicCode($entry['publicCode']);
            $this->fillTemplate($template, $entry);
            $this->entityManager->persist($template);
            ++$created;
        }

        $this->entityManager->flush();

        return ['created' => $created, 'updated' => $updated];
    }

    /**
     * @return list<array{nameKey: string, imageKey: string, rarity: WearableItemRarity}>
     */
    public function shopVariantsForTypeAndLevel(WearableItemType $type, int $playerLevel): array
    {
        $variants = [];
        foreach ($this->templateRepository->findAvailableForTypeAndLevel($type, $playerLevel) as $template) {
            $variants[] = [
                'nameKey' => $template->getNameKey(),
                'imageKey' => $template->getImageKey(),
                'rarity' => $template->getRarity(),
            ];
        }

        return $variants;
    }

    public function applyRandomTemplate(
        WearableItem $item,
        int $playerLevel,
        ?WearableItemType $type = null,
        ?WearableItemRarity $rarity = null,
    ): void {
        $resolvedType = $type ?? $item->getType();
        if (!$resolvedType instanceof WearableItemType) {
            throw new \LogicException('Wearable item type is required to apply catalog template.');
        }

        $resolved = $this->variantResolver->resolve($resolvedType, $playerLevel, 'loot');
        $effectiveLevel = $resolved['effectiveLevel'];

        $template = $this->templatePicker->pickRandomForTypeAndLevel($resolvedType, $effectiveLevel, $rarity);
        if (!$template instanceof WearableItemTemplate) {
            $pick = $this->variantResolver->pickRandomVariant($resolvedType, $playerLevel, $rarity, 'loot');
            $item->setType($resolvedType);
            $item->setNameKey($pick['nameKey']);
            $item->setImageKey($pick['imageKey']);
            $item->setName(WearableItemCatalog::displayNameForKey($pick['nameKey']));
            if ($rarity === null) {
                $item->setRarity($pick['rarity']);
            }

            return;
        }

        $item->setType($resolvedType);
        $item->setNameKey($template->getNameKey());
        $item->setImageKey($template->getImageKey());
        $item->setName(WearableItemCatalog::displayNameForKey($template->getNameKey()));
        if ($rarity === null) {
            $item->setRarity($template->getRarity());
        }
    }

    /**
     * @param array{
     *   publicCode: string,
     *   nameKey: string,
     *   imageKey: string,
     *   type: WearableItemType,
     *   rarity: WearableItemRarity,
     *   minLevel: int,
     *   maxLevel: int,
     * } $entry
     */
    private function fillTemplate(WearableItemTemplate $template, array $entry): void
    {
        $template
            ->setType($entry['type'])
            ->setNameKey($entry['nameKey'])
            ->setImageKey($entry['imageKey'])
            ->setRarity($entry['rarity'])
            ->setMinLevel($entry['minLevel'])
            ->setMaxLevel($entry['maxLevel']);
    }
}
