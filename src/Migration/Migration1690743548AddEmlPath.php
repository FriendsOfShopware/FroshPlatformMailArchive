<?php

declare(strict_types=1);

namespace Frosh\MailArchive\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1690743548AddEmlPath extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1690743548;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('ALTER TABLE `frosh_mail_archive`
                                            ADD `eml_path` varchar(2048) NULL;');
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
