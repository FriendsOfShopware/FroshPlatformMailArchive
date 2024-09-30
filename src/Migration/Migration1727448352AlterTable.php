<?php

declare(strict_types=1);

namespace Frosh\MailArchive\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1727448352AlterTable extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1727448352;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement(
            'ALTER TABLE `frosh_mail_archive` CHANGE `source_mail_id` `parent_id` binary(16) NULL;'
        );

        $connection->executeStatement(
            'ALTER TABLE `frosh_mail_archive` ADD `history_last_mail` TINYINT(1) NOT NULL DEFAULT 0 AFTER `transport_state`;'
        );

        // set history_last_mail column of last row of history
        $result = $connection->executeQuery(
            'SELECT `id` FROM `frosh_mail_archive` WHERE `parent_id` IS NULL'
        );
        foreach ($result->iterateColumn() as $id) {
            $lastId = $connection->fetchOne(
                'SELECT `id` FROM `frosh_mail_archive` WHERE `parent_id` = :parentId ORDER BY created_at DESC LIMIT 1',
                ['parentId' => $id]
            );

            if ($lastId === false) {
                // mail was not resend
                $criteria = ['id' => $id];
            } else {
                $criteria = ['id' => $lastId];
            }
            $connection->update(
                'frosh_mail_archive',
                ['history_last_mail' => 1],
                $criteria
            );
        }
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
