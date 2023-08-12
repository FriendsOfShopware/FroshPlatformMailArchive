<?php declare(strict_types=1);

namespace Frosh\MailArchive\MessageQueue;

use Shopware\Core\Framework\MessageQueue\AsyncMessageInterface;

class MigrateMailMessage implements AsyncMessageInterface
{
    /**
     * @param string[] $ids
     */
    public function __construct(
        public readonly array $ids
    ) {
    }
}
