<?php

declare(strict_types=1);

namespace Frosh\MailArchive\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1713775973AlterSubjectFieldLength extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1713775973;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('ALTER TABLE `frosh_mail_archive` MODIFY `subject` VARCHAR(998) NOT NULL;');
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
