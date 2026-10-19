<?php

declare(strict_types=1);

namespace Frosh\MailArchive\Tests;

use Doctrine\DBAL\Connection;
use Frosh\MailArchive\FroshPlatformMailArchive;
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
    public function testUninstallDropsTablesWhenUserDataIsNotKept(): void
    {
        $statements = [];
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->exactly(2))
            ->method('executeStatement')
            ->willReturnCallback(static function (string $statement) use (&$statements): int {
                $statements[] = $statement;

                return 0;
            });

        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')->with(Connection::class)->willReturn($connection);

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

        static::assertSame([
            'DROP TABLE IF EXISTS frosh_mail_archive_attachment',
            'DROP TABLE IF EXISTS frosh_mail_archive',
        ], $statements);
    }
}
