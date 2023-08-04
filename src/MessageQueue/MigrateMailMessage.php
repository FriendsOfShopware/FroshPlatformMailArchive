<?php declare(strict_types=1);

namespace Frosh\MailArchive\MessageQueue;

class MigrateMailMessage
{
    /**
     * @param string[] $ids
     */
    public function __construct(
        public readonly array $ids
    ) {
    }
}
