<?php declare(strict_types=1);

namespace Frosh\MailArchive\Content\MailArchive;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @extends EntityCollection<MailArchiveAttachmentEntity>
 */
class MailArchiveAttachmentCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return MailArchiveAttachmentEntity::class;
    }
}
