<?php

namespace Frosh\MailArchive\Extension\Content\Flow;

use Frosh\MailArchive\Content\MailArchive\MailArchiveDefinition;
use Shopware\Core\Content\Flow\FlowDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityExtension;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\SetNullOnDelete;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class FlowExtension extends EntityExtension
{
    public function getDefinitionClass(): string
    {
        return FlowDefinition::class;
    }

    public function extendFields(FieldCollection $collection): void
    {
        $collection->add(
            (new OneToManyAssociationField(
                'froshMailArchive',
                MailArchiveDefinition::class,
                'flow_id',
            ))->addFlags(new SetNullOnDelete()),
        );
    }
}
