<?php declare(strict_types=1);

namespace Frosh\MailArchive;

use Doctrine\DBAL\Connection;
use Frosh\MailArchive\MessageQueue\MigrateMailMessage;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;

class FroshPlatformMailArchive extends Plugin
{
    public function uninstall(UninstallContext $uninstallContext): void
    {
        if ($uninstallContext->keepUserData()) {
            return;
        }

        $this->container->get(Connection::class)->executeStatement('DROP TABLE IF EXISTS frosh_mail_archive');
    }

    public function postUpdate(UpdateContext $updateContext): void
    {
        if (\version_compare($updateContext->getCurrentPluginVersion(), '1.0.1', '<=')) {
            $this->migrateMailsToFilesystem();
        }
    }

    private function migrateMailsToFilesystem(): void
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('emlPath', null));

        $froshMailArchiveRepository = $this->container->get('frosh_mail_archive.repository');
        $ids = $froshMailArchiveRepository->searchIds($criteria, Context::createDefaultContext())->getIds();
        $messageBus = $this->container->get('messenger.default_bus');

        foreach (array_chunk($ids, 20) as $chunkedIds) {
            $message = new MigrateMailMessage($chunkedIds);

            $messageBus->dispatch($message);
        }
    }
}
