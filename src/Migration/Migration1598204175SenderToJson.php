<?php

declare(strict_types=1);

namespace Frosh\MailArchive\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1598204175SenderToJson extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1598204175;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('UPDATE frosh_mail_archive SET sender = JSON_OBJECT(sender, \'\')');
        $connection->executeStatement('ALTER TABLE `frosh_mail_archive`
CHANGE `sender` `sender` json NOT NULL AFTER `id`;');
    }

    public function updateDestructive(Connection $connection): void {}
}
