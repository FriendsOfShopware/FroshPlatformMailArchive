<?php

declare(strict_types=1);

namespace Frosh\MailArchive\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1575569953createTable extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1575569953;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('DROP TABLE IF EXISTS frosh_mail_archive_attachment');
        $connection->executeStatement('DROP TABLE IF EXISTS frosh_mail_archive');

        $connection->executeStatement('CREATE TABLE `frosh_mail_archive` (
    `id` BINARY(16) NOT NULL,
    `sender` VARCHAR(255) NOT NULL,
    `receiver` JSON NOT NULL,
    `subject` VARCHAR(255) NOT NULL,
    `plainText` LONGTEXT NULL,
    `htmlText` LONGTEXT NULL,
    `eml` LONGTEXT NULL,
    `salesChannelId` BINARY(16) NULL,
    `customerId` BINARY(16) NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `json.frosh_mail_archive.receiver` CHECK (JSON_VALID(`receiver`)),
    KEY `fk.frosh_mail_archive.salesChannelId` (`salesChannelId`),
    KEY `fk.frosh_mail_archive.customerId` (`customerId`),
    CONSTRAINT `fk.frosh_mail_archive.salesChannelId` FOREIGN KEY (`salesChannelId`) REFERENCES `sales_channel` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk.frosh_mail_archive.customerId` FOREIGN KEY (`customerId`) REFERENCES `customer` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
');
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
