<?php

declare(strict_types=1);

namespace Frosh\MailArchive\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1795698726AddTransportStateIndex extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1795698726;
    }

    public function update(Connection $connection): void
    {
        if ($this->indexExists($connection, 'frosh_mail_archive', 'idx.frosh_mail_archive.transport_state')) {
            return;
        }

        $connection->executeStatement('ALTER TABLE `frosh_mail_archive` ADD INDEX `idx.frosh_mail_archive.transport_state` (`transport_state`)');
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
