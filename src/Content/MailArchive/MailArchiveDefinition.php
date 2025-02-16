<?php

declare(strict_types=1);

namespace Frosh\MailArchive\Content\MailArchive;

use Shopware\Core\Checkout\Customer\CustomerDefinition;
use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Content\Flow\FlowDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\AllowHtml;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\CascadeDelete;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\SearchRanking;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\JsonField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\LongTextField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\System\SalesChannel\SalesChannelDefinition;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('shopware.entity.definition')]
class MailArchiveDefinition extends EntityDefinition
{
    final public const ENTITY_NAME = 'frosh_mail_archive';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return MailArchiveEntity::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required()),
            (new JsonField('sender', 'sender'))->addFlags(new Required()),
            (new JsonField('receiver', 'receiver'))->addFlags(new Required())->addFlags(new SearchRanking(SearchRanking::HIGH_SEARCH_RANKING)),
            (new StringField('subject', 'subject', 998))->addFlags(new Required())->addFlags(new SearchRanking(SearchRanking::HIGH_SEARCH_RANKING)),
            (new LongTextField('plainText', 'plainText'))->addFlags(new AllowHtml()),
            (new LongTextField('htmlText', 'htmlText'))->addFlags(new AllowHtml(), new SearchRanking(SearchRanking::LOW_SEARCH_RANKING)),
            (new StringField('eml_path', 'emlPath', 2048)),
            (new StringField('transport_state', 'transportState'))->addFlags(new Required()),

            (new OneToManyAssociationField('attachments', MailArchiveAttachmentDefinition::class, 'mail_archive_id', 'id'))->addFlags(new CascadeDelete()),

            new FkField('salesChannelId', 'salesChannelId', SalesChannelDefinition::class),
            new ManyToOneAssociationField('salesChannel', 'salesChannelId', SalesChannelDefinition::class, 'id', true),

            new FkField('customerId', 'customerId', CustomerDefinition::class),
            new ManyToOneAssociationField('customer', 'customerId', CustomerDefinition::class, 'id', true),

            new FkField('order_id', 'orderId', OrderDefinition::class),
            new ManyToOneAssociationField('order', 'order_id', OrderDefinition::class, 'id', false),

            new FkField('flow_id', 'flowId', FlowDefinition::class),
            new ManyToOneAssociationField('flow', 'flow_id', FlowDefinition::class, 'id', false),

            new FkField('source_mail_id', 'sourceMailId', self::class),
            new ManyToOneAssociationField('sourceMail', 'source_mail_id', self::class, 'id', false),
            new OneToManyAssociationField('sourceMails', self::class, 'sourceMailId'),
        ]);
    }
}
