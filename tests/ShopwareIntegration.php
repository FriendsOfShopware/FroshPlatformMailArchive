<?php

declare(strict_types=1);

namespace Frosh\MailArchive\Tests;

use Shopware\Core\TestBootstrapper;
use Symfony\Component\Console\Output\NullOutput;

final class ShopwareIntegration
{
    private static bool $booted = false;

    /**
     * Boots Shopware against its test database and installs this plugin.
     * Returns false when this checkout is not inside a Shopware project.
     */
    public static function bootIfAvailable(): bool
    {
        if (self::$booted) {
            return true;
        }

        $projectDir = self::projectDir();
        if (!\is_file($projectDir . '/vendor/shopware/core/TestBootstrapper.php')) {
            return false;
        }

        $_SERVER['APP_ENV'] = $_ENV['APP_ENV'] = 'test';
        $_SERVER['APP_DEBUG'] = $_ENV['APP_DEBUG'] = '1';

        (new TestBootstrapper())
            ->setProjectDir($projectDir)
            ->setOutput(new NullOutput())
            ->addCallingPlugin()
            ->addActivePlugins('FroshPlatformMailArchive')
            ->setForceInstallPlugins(true)
            ->bootstrap();

        self::$booted = true;

        return true;
    }

    private static function projectDir(): string
    {
        $configured = $_SERVER['PROJECT_ROOT'] ?? $_ENV['PROJECT_ROOT'] ?? null;
        if (\is_string($configured) && \is_file($configured . '/vendor/shopware/core/TestBootstrapper.php')) {
            return $configured;
        }

        return \dirname(__DIR__, 4);
    }
}
