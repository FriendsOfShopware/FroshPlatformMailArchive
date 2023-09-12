<?php

declare(strict_types=1);

namespace Frosh\MailArchive\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1694519020TransportFailed extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1694519020;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('ALTER TABLE `frosh_mail_archive` ADD `transport_failed` TINYINT(1) NOT NULL DEFAULT 0;');
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
