<?php

declare(strict_types=1);

namespace Frosh\MailArchive\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1770821445AddMailTemplateId extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1770821445;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement("ALTER TABLE `frosh_mail_archive` ADD COLUMN `mail_template_id` BINARY(16) NULL");
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
