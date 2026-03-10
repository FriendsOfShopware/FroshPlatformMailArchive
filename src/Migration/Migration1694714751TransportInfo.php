<?php

declare(strict_types=1);

namespace Frosh\MailArchive\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1694714751TransportInfo extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1694714751;
    }

    public function update(Connection $connection): void
    {
        if ($this->columnExists($connection, 'frosh_mail_archive', 'transport_state')) {
            return;
        }
        $connection->executeStatement('ALTER TABLE `frosh_mail_archive` ADD `transport_state` VARCHAR(255) NULL;');
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
