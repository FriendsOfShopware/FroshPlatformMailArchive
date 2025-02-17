<?php

declare(strict_types=1);

namespace Frosh\MailArchive\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1739730285AddOrderId extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1739730285;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('
            ALTER TABLE `frosh_mail_archive`
            ADD COLUMN `order_id` BINARY(16) NULL,
            ADD COLUMN `order_version_id` BINARY(16) NULL
            ;
        ');
    }

    public function updateDestructive(Connection $connection): void {}
}
