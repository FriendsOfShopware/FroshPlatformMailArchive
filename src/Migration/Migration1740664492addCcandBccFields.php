<?php declare(strict_types=1);

namespace Frosh\MailArchive\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

/**
 * @internal
 */
class Migration1740664492addCcandBccFields extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1740664492;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
ALTER TABLE `frosh_mail_archive` ADD `cc` JSON NULL AFTER subject;
ALTER TABLE `frosh_mail_archive` ADD `bcc` JSON NULL AFTER cc;
SQL;

        $connection->executeStatement($sql);
    }
}
