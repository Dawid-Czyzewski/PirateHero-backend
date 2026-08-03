<?php

declare(strict_types=1);

namespace App\Service\Progression;

use App\Entity\QuestTemplate;
use App\Entity\User;
use App\Entity\UserQuest;
use App\Entity\UserStatistics;
use App\Repository\QuestTemplateRepository;
use App\Repository\UserQuestRepository;
use Doctrine\ORM\EntityManagerInterface;

final class UserQuestInitializer
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserQuestRepository $userQuestRepository,
        private readonly QuestTemplateRepository $questTemplateRepository,
        private readonly DefaultQuestTemplatesProvisioner $defaultQuestTemplatesProvisioner,
        private readonly QuestProgressEvaluator $questProgressEvaluator,
    ) {
    }

    public function initializeUserQuests(User $user): void
    {
        $this->defaultQuestTemplatesProvisioner->ensureActiveTemplatesExist();
        $this->defaultQuestTemplatesProvisioner->ensureItemCollectionQuestTemplatesMissing();
        $this->defaultQuestTemplatesProvisioner->ensureThursdayQuestTemplatesMissing();
        $this->defaultQuestTemplatesProvisioner->ensureFridayQuestTemplatesMissing();
        $this->defaultQuestTemplatesProvisioner->ensureMondayQuestTemplatesMissing();
        $this->defaultQuestTemplatesProvisioner->ensureTuesdayQuestTemplatesMissing();
        $this->defaultQuestTemplatesProvisioner->ensureWednesdayQuestTemplatesMissing();
        $this->defaultQuestTemplatesProvisioner->ensureThursdayContentExpansionQuestTemplatesMissing();
        $this->defaultQuestTemplatesProvisioner->ensureQuestTemplateCodesBackfilled();

        if ($this->isTestAccount($user)) {
            $this->defaultQuestTemplatesProvisioner->ensureTestItemClaimQuestTemplatesMissing();
        }

        $userStatistics = $user->getUserStatistics();
        if (!$userStatistics) {
            $userStatistics = new UserStatistics();
            $userStatistics->setUser($user);
            $userStatistics->setLevelsReached($user->getLevel() ? (int) $user->getLevel()->getName() : 1);
            $user->setUserStatistics($userStatistics);
            $this->entityManager->persist($userStatistics);
            $this->entityManager->flush();
        }

        $templates = $this->questTemplateRepository->findActiveOrdered();

        foreach ($templates as $template) {
            $existing = $this->userQuestRepository->findByUserAndTemplate($user, $template->getId());
            if (!$existing) {
                $userQuest = new UserQuest();
                $userQuest->setUser($user);
                $userQuest->setQuestTemplate($template);

                $currentValue = $this->questProgressEvaluator->getCurrentValueForCategory($userStatistics, $template, $user);
                $userQuest->setCurrentProgress(min($currentValue, $template->getTargetValue()));

                $this->entityManager->persist($userQuest);
            }
        }

        $this->entityManager->flush();

        if ($this->isTestAccount($user)) {
            $this->markTestAccountQuestsReadyForClaim($user);
            $this->entityManager->flush();
        }
    }

    private function isTestAccount(User $user): bool
    {
        return strcasecmp((string) $user->getEmail(), QuestTemplateDefaults::TEST_ACCOUNT_EMAIL) === 0;
    }

    private function markTestAccountQuestsReadyForClaim(User $user): void
    {
        $titles = QuestTemplateDefaults::testAccountReadyItemClaimTemplateTitles();
        foreach ($titles as $title) {
            $template = $this->questTemplateRepository->findOneBy(['title' => $title]);
            if (!$template instanceof QuestTemplate || !$template->isActive()) {
                continue;
            }

            $userQuest = $this->userQuestRepository->findByUserAndTemplate($user, $template->getId());
            if (!$userQuest instanceof UserQuest) {
                continue;
            }

            if ($userQuest->isRewardClaimed()) {
                continue;
            }

            $userQuest->setCurrentProgress($template->getTargetValue());
            $this->entityManager->persist($userQuest);
        }
    }
}
