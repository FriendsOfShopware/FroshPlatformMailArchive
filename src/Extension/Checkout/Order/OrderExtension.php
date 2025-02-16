<?php

namespace Frosh\MailArchive\Extension\Checkout\Order;

use Frosh\MailArchive\Content\MailArchive\MailArchiveDefinition;
use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityExtension;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\SetNullOnDelete;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class OrderExtension extends EntityExtension
{
    public function getDefinitionClass(): string
    {
        return OrderDefinition::class;
    }

    public function extendFields(FieldCollection $collection): void
    {
        $collection->add(
            (new OneToManyAssociationField(
                'froshMailArchive',
                MailArchiveDefinition::class,
                'order_id',
            ))->addFlags(new SetNullOnDelete()),
        );
    }
}
