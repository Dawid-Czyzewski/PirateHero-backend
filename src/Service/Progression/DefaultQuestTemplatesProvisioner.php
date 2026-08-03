<?php

declare(strict_types=1);

namespace App\Service\Progression;

use App\Repository\QuestTemplateRepository;
use Doctrine\ORM\EntityManagerInterface;

class DefaultQuestTemplatesProvisioner
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private QuestTemplateRepository $questTemplateRepository,
    ) {
    }

    public function ensureActiveTemplatesExist(): void
    {
        $count = (int) $this->questTemplateRepository->createQueryBuilder('qt')
            ->select('COUNT(qt.id)')
            ->where('qt.isActive = :active')
            ->setParameter('active', true)
            ->getQuery()
            ->getSingleScalarResult();

        if ($count > 0) {
            return;
        }

        foreach (QuestTemplateDefaults::createActiveTemplates() as $template) {
            $this->entityManager->persist($template);
        }

        $this->entityManager->flush();
    }

    public function ensureStarterTemplatesMissing(): void
    {
        $dirty = false;
        foreach (QuestTemplateDefaults::createStarterTemplates() as $template) {
            $existing = $this->questTemplateRepository->findOneBy(['title' => $template->getTitle()]);
            if ($existing !== null) {
                continue;
            }

            $this->entityManager->persist($template);
            $dirty = true;
        }

        if ($dirty) {
            $this->entityManager->flush();
        }
    }

    public function ensureTestItemClaimQuestTemplatesMissing(): void
    {
        $dirty = false;
        foreach (QuestTemplateDefaults::createTestItemClaimTemplates() as $template) {
            $existing = $this->questTemplateRepository->findOneBy(['title' => $template->getTitle()]);
            if ($existing !== null) {
                continue;
            }

            $this->entityManager->persist($template);
            $dirty = true;
        }

        if ($dirty) {
            $this->entityManager->flush();
        }
    }

    public function ensureItemCollectionQuestTemplatesMissing(): void
    {
        $dirty = false;
        $titles = [
            'Pierwsze 10 Zdobytych Przedmiotów',
            'Pierwszy Rare Item',
            'Pierwszy Pełny Ekwipunek',
        ];

        foreach (QuestTemplateDefaults::createActiveTemplates() as $template) {
            if (!\in_array($template->getTitle(), $titles, true)) {
                continue;
            }

            $existing = $this->questTemplateRepository->findOneBy(['title' => $template->getTitle()]);
            if ($existing !== null) {
                continue;
            }

            $this->entityManager->persist($template);
            $dirty = true;
        }

        if ($dirty) {
            $this->entityManager->flush();
        }
    }

    public function ensureThursdayQuestTemplatesMissing(): void
    {
        $dirty = false;
        $titles = [
            'Kolekcjoner II',
            'Pełny zestaw Rare',
            'Weteran walk',
            'Mistrz lochów',
        ];

        foreach (QuestTemplateDefaults::createActiveTemplates() as $template) {
            if (!\in_array($template->getTitle(), $titles, true)) {
                continue;
            }

            $existing = $this->questTemplateRepository->findOneBy(['title' => $template->getTitle()]);
            if ($existing !== null) {
                continue;
            }

            $this->entityManager->persist($template);
            $dirty = true;
        }

        if ($dirty) {
            $this->entityManager->flush();
        }
    }

    public function ensureFridayQuestTemplatesMissing(): void
    {
        $dirty = false;
        $titles = [
            'Kolekcjoner III',
            'Pogromca Piratów',
            'Legenda Mórz',
        ];

        foreach (QuestTemplateDefaults::createActiveTemplates() as $template) {
            if (!\in_array($template->getTitle(), $titles, true)) {
                continue;
            }

            $existing = $this->questTemplateRepository->findOneBy(['title' => $template->getTitle()]);
            if ($existing !== null) {
                continue;
            }

            $this->entityManager->persist($template);
            $dirty = true;
        }

        if ($dirty) {
            $this->entityManager->flush();
        }
    }

    public function ensureMondayQuestTemplatesMissing(): void
    {
        $dirty = false;
        $titles = [
            'Kolekcjoner IV',
            'Pogromca Potworów',
            'Zdobywca Legend',
            'Weteran Poziomów',
        ];

        foreach (QuestTemplateDefaults::createActiveTemplates() as $template) {
            if (!\in_array($template->getTitle(), $titles, true)) {
                continue;
            }

            $existing = $this->questTemplateRepository->findOneBy(['title' => $template->getTitle()]);
            if ($existing !== null) {
                continue;
            }

            $this->entityManager->persist($template);
            $dirty = true;
        }

        if ($dirty) {
            $this->entityManager->flush();
        }
    }

    public function ensureTuesdayQuestTemplatesMissing(): void
    {
        $dirty = false;
        $codes = QuestTemplateDefaults::tuesdayQuestTemplateCodes();

        foreach (QuestTemplateDefaults::createActiveTemplates() as $template) {
            $code = $template->getCode();
            if ($code === null || !\in_array($code, $codes, true)) {
                continue;
            }

            $existing = $this->questTemplateRepository->findOneBy(['code' => $code]);
            if ($existing !== null) {
                continue;
            }

            $this->entityManager->persist($template);
            $dirty = true;
        }

        if ($dirty) {
            $this->entityManager->flush();
        }
    }

    public function ensureWednesdayQuestTemplatesMissing(): void
    {
        $dirty = false;
        $codes = QuestTemplateDefaults::wednesdayQuestTemplateCodes();

        foreach (QuestTemplateDefaults::createActiveTemplates() as $template) {
            $code = $template->getCode();
            if ($code === null || !\in_array($code, $codes, true)) {
                continue;
            }

            $existing = $this->questTemplateRepository->findOneBy(['code' => $code]);
            if ($existing !== null) {
                continue;
            }

            $this->entityManager->persist($template);
            $dirty = true;
        }

        if ($dirty) {
            $this->entityManager->flush();
        }
    }

    public function ensureThursdayContentExpansionQuestTemplatesMissing(): void
    {
        $dirty = false;
        $codes = QuestTemplateDefaults::thursdayContentExpansionQuestTemplateCodes();

        foreach (QuestTemplateDefaults::createActiveTemplates() as $template) {
            $code = $template->getCode();
            if ($code === null || !\in_array($code, $codes, true)) {
                continue;
            }

            $existing = $this->questTemplateRepository->findOneBy(['code' => $code]);
            if ($existing !== null) {
                continue;
            }

            $this->entityManager->persist($template);
            $dirty = true;
        }

        $titlesAll = $this->questTemplateRepository->findOneBy(['code' => 'titles_all_unlocked']);
        if ($titlesAll !== null && $titlesAll->getTargetValue() !== 54) {
            $titlesAll->setTargetValue(54);
            $dirty = true;
        }

        if ($dirty) {
            $this->entityManager->flush();
        }
    }

    public function ensureQuestTemplateCodesBackfilled(): void
    {
        $dirty = false;
        foreach (QuestTemplateDefaults::createActiveTemplates() as $defaults) {
            $code = $defaults->getCode();
            if ($code === null) {
                continue;
            }

            $existing = $this->questTemplateRepository->findOneBy(['title' => $defaults->getTitle()]);
            if ($existing === null) {
                continue;
            }

            if ($existing->getCode() !== $code) {
                $existing->setCode($code);
                $dirty = true;
            }

            if ($defaults->getTargetDungeonId() !== null && $existing->getTargetDungeonId() !== $defaults->getTargetDungeonId()) {
                $existing->setTargetDungeonId($defaults->getTargetDungeonId());
                $dirty = true;
            }
        }

        if ($dirty) {
            $this->entityManager->flush();
        }
    }
}
