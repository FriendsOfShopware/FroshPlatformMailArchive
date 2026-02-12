<?php

declare(strict_types=1);

namespace Frosh\MailArchive\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1694604822AddSourceMailId extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1694604822;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('ALTER TABLE `frosh_mail_archive` ADD `source_mail_id` BINARY(16) NULL;');
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
