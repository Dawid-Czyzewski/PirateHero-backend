<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Progression;

use App\Repository\QuestTemplateRepository;
use App\Service\Progression\DefaultQuestTemplatesProvisioner;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;

final class DefaultQuestTemplatesProvisionerTest extends TestCase
{
    public function testEnsureDoesNothingWhenActiveTemplatesExist(): void
    {
        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('select')->willReturnSelf();
        $qb->method('where')->willReturnSelf();
        $qb->method('setParameter')->willReturnSelf();
        $query = $this->createMock(Query::class);
        $query->method('getSingleScalarResult')->willReturn('3');
        $qb->method('getQuery')->willReturn($query);

        $repo = $this->createMock(QuestTemplateRepository::class);
        $repo->method('createQueryBuilder')->willReturn($qb);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::never())->method('persist');

        $sut = new DefaultQuestTemplatesProvisioner($em, $repo);
        $sut->ensureActiveTemplatesExist();
    }

    public function testEnsurePersistsDefaultsWhenNoActiveTemplates(): void
    {
        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('select')->willReturnSelf();
        $qb->method('where')->willReturnSelf();
        $qb->method('setParameter')->willReturnSelf();
        $query = $this->createMock(Query::class);
        $query->method('getSingleScalarResult')->willReturn('0');
        $qb->method('getQuery')->willReturn($query);

        $repo = $this->createMock(QuestTemplateRepository::class);
        $repo->method('createQueryBuilder')->willReturn($qb);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::atLeastOnce())->method('persist');
        $em->expects(self::once())->method('flush');

        $sut = new DefaultQuestTemplatesProvisioner($em, $repo);
        $sut->ensureActiveTemplatesExist();
    }

    public function testEnsureStarterTemplatesMissingPersistsWhenTitlesAbsent(): void
    {
        $repo = $this->createMock(QuestTemplateRepository::class);
        $repo->method('findOneBy')->willReturn(null);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::exactly(4))->method('persist');
        $em->expects(self::once())->method('flush');

        $sut = new DefaultQuestTemplatesProvisioner($em, $repo);
        $sut->ensureStarterTemplatesMissing();
    }
}
