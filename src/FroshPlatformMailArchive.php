<?php declare(strict_types=1);

namespace Frosh\MailArchive;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;

class FroshPlatformMailArchive extends Plugin
{
    public function uninstall(UninstallContext $uninstallContext): void
    {
        if ($uninstallContext->keepUserData()) {
            return;
        }

        $this->container->get(Connection::class)->executeStatement('DROP TABLE IF EXISTS frosh_mail_archive');
    }
}
