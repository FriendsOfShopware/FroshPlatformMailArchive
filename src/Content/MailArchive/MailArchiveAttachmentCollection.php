<?php

declare(strict_types=1);

namespace Frosh\MailArchive\Content\MailArchive;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @extends EntityCollection<MailArchiveAttachmentEntity>
 */
class MailArchiveAttachmentCollection extends EntityCollection
{
    public function getApiAlias(): string
    {
        return 'frosh_mail_archive_attachment_collection';
    }

    protected function getExpectedClass(): string
    {
        return MailArchiveAttachmentEntity::class;
    }
}
