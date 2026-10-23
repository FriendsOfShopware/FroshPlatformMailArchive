<?php

declare(strict_types=1);

namespace Frosh\MailArchive\Task;

use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTask;

class CleanupTask extends ScheduledTask
{
    final public const NAME = 'frosh.mail_archive.cleanup';

    public static function getTaskName(): string
    {
        return self::NAME;
    }

    public static function getDefaultInterval(): int
    {
        return 86400; // 1 day
    }
}
