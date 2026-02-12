<?php

declare(strict_types=1);

namespace Frosh\MailArchive\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1707002823DropEmlFiled extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1707002823;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('ALTER TABLE `frosh_mail_archive` DROP COLUMN `eml`');
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
