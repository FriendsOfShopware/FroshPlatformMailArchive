<?php declare(strict_types=1);

namespace Frosh\MailArchive;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class FroshPlatformMailArchive extends Plugin
{
    public function install(InstallContext $installContext): void
    {
        $this->container->get(SystemConfigService::class)->set('FroshPlatformMailArchive.config.deleteMessageAfterDays', 90);
    }

    public function uninstall(UninstallContext $uninstallContext): void
    {
        if ($uninstallContext->keepUserData()) {
            return;
        }

        $this->container->get(Connection::class)->executeQuery('DROP TABLE IF EXISTS frosh_mail_archive');
    }

}
