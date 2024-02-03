<?php

declare(strict_types=1);

namespace Frosh\MailArchive\Content\MailArchive;

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('shopware.entity.definition')]
class MailArchiveAttachmentDefinition extends EntityDefinition
{
    final public const ENTITY_NAME = 'frosh_mail_archive_attachment';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return MailArchiveAttachmentEntity::class;
    }

    public function getCollectionClass(): string
    {
        return MailArchiveAttachmentCollection::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required()),
            new FkField('mail_archive_id', 'mailArchiveId', MailArchiveDefinition::class),
            new ManyToOneAssociationField('mailArchive', 'mail_archive_id', MailArchiveDefinition::class, 'id', false),

            (new StringField('file_name', 'fileName'))->addFlags(new Required()),
            (new StringField('content_type', 'contentType'))->addFlags(new Required()),
            (new IntField('file_size', 'fileSize'))->addFlags(new Required()),
        ]);
    }
}
