<?php

declare(strict_types=1);

namespace Frosh\MailArchive\Tests;

use Doctrine\DBAL\Connection;
use Frosh\MailArchive\FroshPlatformMailArchive;
use League\Flysystem\FilesystemOperator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Api\Context\SystemSource;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Migration\MigrationCollection;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @internal
 */
#[CoversClass(FroshPlatformMailArchive::class)]
class FroshPlatformMailArchiveTest extends TestCase
{
    public function testUninstallRemovesArchivedFilesWhenUserDataIsNotKept(): void
    {
        $connection = $this->createMock(Connection::class);
        $filesystem = $this->createMock(FilesystemOperator::class);
        $filesystem->expects($this->once())->method('directoryExists')->with('mails')->willReturn(true);
        $filesystem->expects($this->once())->method('deleteDirectory')->with('mails');

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->with('frosh_platform_mail_archive.filesystem.private')->willReturn(true);
        $container->method('get')->willReturnMap([
            [Connection::class, ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $connection],
            ['frosh_platform_mail_archive.filesystem.private', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $filesystem],
        ]);

        $plugin = new FroshPlatformMailArchive(true, \dirname(__DIR__));
        $plugin->setContainer($container);
        $context = new UninstallContext(
            $plugin,
            new Context(new SystemSource()),
            '6.7.0.0',
            '3.6.0',
            $this->createMock(MigrationCollection::class),
            false,
        );

        $plugin->uninstall($context);
    }
}
