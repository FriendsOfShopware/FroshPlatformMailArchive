<?php

declare(strict_types=1);

namespace Frosh\MailArchive;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Symfony\Component\DependencyInjection\ContainerInterface;

class FroshPlatformMailArchive extends Plugin
{
    public function uninstall(UninstallContext $uninstallContext): void
    {
        if ($uninstallContext->keepUserData()) {
            return;
        }

        $container = $this->container;
        if (!$container instanceof ContainerInterface) {
            return;
        }

        $connection = $container->get(Connection::class);
        if (!$connection instanceof Connection) {
            return;
        }

        $connection->executeStatement('DROP TABLE IF EXISTS frosh_mail_archive_attachment');
        $connection->executeStatement('DROP TABLE IF EXISTS frosh_mail_archive');
    }

    public function executeComposerCommands(): bool
    {
        return true;
    }
}
