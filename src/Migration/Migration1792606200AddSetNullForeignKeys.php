<?php

declare(strict_types=1);

namespace Frosh\MailArchive\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1792606200AddSetNullForeignKeys extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1792606200;
    }

    public function update(Connection $connection): void
    {
        $this->nullOrphanedReferences($connection);

        $this->addForeignKeyIfMissing(
            $connection,
            'fk.frosh_mail_archive.customerId',
            'ALTER TABLE `frosh_mail_archive`
                ADD CONSTRAINT `fk.frosh_mail_archive.customerId`
                FOREIGN KEY (`customerId`) REFERENCES `customer` (`id`)
                ON DELETE SET NULL ON UPDATE CASCADE'
        );
        $this->addForeignKeyIfMissing(
            $connection,
            'fk.frosh_mail_archive.order_id',
            'ALTER TABLE `frosh_mail_archive`
                ADD CONSTRAINT `fk.frosh_mail_archive.order_id`
                FOREIGN KEY (`order_id`, `order_version_id`) REFERENCES `order` (`id`, `version_id`)
                ON DELETE SET NULL ON UPDATE CASCADE'
        );
        $this->addForeignKeyIfMissing(
            $connection,
            'fk.frosh_mail_archive.flow_id',
            'ALTER TABLE `frosh_mail_archive`
                ADD CONSTRAINT `fk.frosh_mail_archive.flow_id`
                FOREIGN KEY (`flow_id`) REFERENCES `flow` (`id`)
                ON DELETE SET NULL ON UPDATE CASCADE'
        );
    }

    public function updateDestructive(Connection $connection): void
    {
    }

    private function nullOrphanedReferences(Connection $connection): void
    {
        $connection->executeStatement('
            UPDATE `frosh_mail_archive` AS `archive`
            LEFT JOIN `customer` AS `customer` ON `archive`.`customerId` = `customer`.`id`
            SET `archive`.`customerId` = NULL
            WHERE `archive`.`customerId` IS NOT NULL
              AND `customer`.`id` IS NULL
        ');

        $connection->executeStatement('
            UPDATE `frosh_mail_archive` AS `archive`
            LEFT JOIN `order` AS `referenced_order`
                ON `archive`.`order_id` = `referenced_order`.`id`
               AND `archive`.`order_version_id` = `referenced_order`.`version_id`
            SET `archive`.`order_id` = NULL,
                `archive`.`order_version_id` = NULL
            WHERE `archive`.`order_id` IS NOT NULL
              AND `referenced_order`.`id` IS NULL
        ');

        $connection->executeStatement('
            UPDATE `frosh_mail_archive` AS `archive`
            LEFT JOIN `flow` AS `flow` ON `archive`.`flow_id` = `flow`.`id`
            SET `archive`.`flow_id` = NULL
            WHERE `archive`.`flow_id` IS NOT NULL
              AND `flow`.`id` IS NULL
        ');
    }

    private function addForeignKeyIfMissing(Connection $connection, string $constraintName, string $statement): void
    {
        if ($this->foreignKeyExists($connection, $constraintName)) {
            return;
        }

        $connection->executeStatement($statement);
    }

    private function foreignKeyExists(Connection $connection, string $constraintName): bool
    {
        return (bool) $connection->fetchOne(
            'SELECT CONSTRAINT_NAME
             FROM information_schema.TABLE_CONSTRAINTS
             WHERE CONSTRAINT_SCHEMA = DATABASE()
               AND TABLE_NAME = :tableName
               AND CONSTRAINT_NAME = :constraintName
               AND CONSTRAINT_TYPE = :constraintType',
            [
                'tableName' => 'frosh_mail_archive',
                'constraintName' => $constraintName,
                'constraintType' => 'FOREIGN KEY',
            ]
        );
    }
}
