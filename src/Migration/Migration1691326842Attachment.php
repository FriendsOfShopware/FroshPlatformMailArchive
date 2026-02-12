<?php

declare(strict_types=1);

namespace Frosh\MailArchive\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1691326842Attachment extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1691326842;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('CREATE TABLE `frosh_mail_archive_attachment` (
    `id` BINARY(16) NOT NULL,
    `mail_archive_id` BINARY(16) NULL,
    `file_name` VARCHAR(255) NOT NULL,
    `content_type` VARCHAR(255) NOT NULL,
    `file_size` INT(11) NOT NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`),
    KEY `fk.frosh_mail_archive_attachment.mail_archive_id` (`mail_archive_id`),
    CONSTRAINT `fk.frosh_mail_archive_attachment.mail_archive_id` FOREIGN KEY (`mail_archive_id`) REFERENCES `frosh_mail_archive` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
');
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
