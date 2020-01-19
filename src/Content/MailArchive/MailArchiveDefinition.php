<?php

namespace Frosh\MailArchive\Content\MailArchive;

use Shopware\Core\Checkout\Customer\CustomerDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\JsonField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\LongTextField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\LongTextWithHtmlField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\System\SalesChannel\SalesChannelDefinition;

class MailArchiveDefinition extends EntityDefinition
{
    public function getEntityName(): string
    {
        return 'frosh_mail_archive';
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required()),
            (new StringField('sender', 'sender'))->addFlags(new Required()),
            (new JsonField('receiver', 'receiver'))->addFlags(new Required()),
            (new StringField('subject', 'subject'))->addFlags(new Required()),
            (new LongTextWithHtmlField('plainText', 'plainText')),
            (new LongTextWithHtmlField('htmlText', 'htmlText')),
            (new LongTextField('eml', 'eml')),

            new FkField('salesChannelId', 'salesChannelId', SalesChannelDefinition::class),
            new ManyToOneAssociationField('salesChannel', 'salesChannelId', SalesChannelDefinition::class, 'id', true),

            new FkField('customerId', 'customerId', CustomerDefinition::class),
            new ManyToOneAssociationField('customer', 'customerId', CustomerDefinition::class, 'id', true),
        ]);
    }
}
