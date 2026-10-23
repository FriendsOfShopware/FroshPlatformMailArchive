<?php

declare(strict_types=1);

namespace Frosh\MailArchive\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1739741754AddFlowId extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1739741754;
    }

    public function update(Connection $connection): void
    {
        if ($this->columnExists($connection, 'frosh_mail_archive', 'flow_id')) {
            return;
        }
        $connection->executeStatement('
            ALTER TABLE `frosh_mail_archive`
            ADD COLUMN `flow_id` BINARY(16) NULL;');
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
