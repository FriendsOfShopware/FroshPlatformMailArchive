<?php

declare(strict_types=1);

namespace Frosh\MailArchive\Migration;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1739273849MigrateResentState extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1739273849;
    }

    public function update(Connection $connection): void
    {
        $sourceMailIds = $connection->fetchFirstColumn('SELECT source_mail_id FROM frosh_mail_archive WHERE source_mail_id IS NOT NULL GROUP BY source_mail_id;');

        if (empty($sourceMailIds)) {
            return;
        }

        $updateQuery = $connection->createQueryBuilder();
        $updateQuery->update('frosh_mail_archive');
        $updateQuery->set('transport_state', '\'resent\'');
        $updateQuery->where('id IN (:ids)');

        foreach (array_chunk($sourceMailIds, 1000) as $chunk) {
            $updateQuery->setParameter('ids', $chunk, ArrayParameterType::BINARY);
            $updateQuery->executeStatement();
        }
    }

    public function updateDestructive(Connection $connection): void {}
}
