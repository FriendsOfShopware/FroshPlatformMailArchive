<?php

declare(strict_types=1);

namespace Frosh\MailArchive\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1739731953DropCustomerFK extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1739731953;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('
            ALTER TABLE `frosh_mail_archive`
            DROP FOREIGN KEY `fk.frosh_mail_archive.customerId`;
        ');
    }

    public function updateDestructive(Connection $connection): void {}
}
