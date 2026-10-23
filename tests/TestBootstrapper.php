<?php

declare(strict_types=1);

use Composer\Autoload\ClassLoader;

$classLoader = require \dirname(__DIR__, 4) . '/vendor/autoload.php';
if (!$classLoader instanceof ClassLoader) {
    throw new RuntimeException('Composer did not return its class loader.');
}

$classLoader->addPsr4('Frosh\\MailArchive\\', \dirname(__DIR__) . '/src');
$classLoader->addPsr4('Frosh\\MailArchive\\Tests\\', __DIR__);

return $classLoader;
