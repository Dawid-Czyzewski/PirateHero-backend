<?php

declare(strict_types=1);

namespace App\Character\Infrastructure\Persistence\Doctrine\Schema;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\ForeignKeyConstraint\ReferentialAction;
use Doctrine\DBAL\Schema\TableEditor;
use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;
use Doctrine\ORM\Tools\ToolEvents;

#[AsDoctrineListener(event: ToolEvents::postGenerateSchema)]
final class CharacterOwnerForeignKey
{
    public const NAME = 'fk_characters_user_id';

    public function postGenerateSchema(GenerateSchemaEventArgs $args): void
    {
        $schema = $args->getSchema();

        if (!$schema->hasTable('characters') || !$schema->hasTable('users')) {
            return;
        }

        $foreignKey = ForeignKeyConstraint::editor()
            ->setUnquotedName(self::NAME)
            ->setUnquotedReferencingColumnNames('user_id')
            ->setUnquotedReferencedTableName('users')
            ->setUnquotedReferencedColumnNames('id')
            ->setOnDeleteAction(ReferentialAction::CASCADE)
            ->create();

        $args->setSchema(
            $schema->edit()
                ->modifyTableByUnquotedName('characters', static function (TableEditor $table) use ($foreignKey): void {
                    $table->addForeignKeyConstraint($foreignKey);
                })
                ->create(),
        );
    }
}
