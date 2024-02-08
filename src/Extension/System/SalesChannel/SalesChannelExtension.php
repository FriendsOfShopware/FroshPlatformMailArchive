<?php

declare(strict_types=1);

namespace Frosh\MailArchive\Extension\System\SalesChannel;

use Frosh\MailArchive\Content\MailArchive\MailArchiveDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityExtension;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\System\SalesChannel\SalesChannelDefinition;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('shopware.entity.extension')]
class SalesChannelExtension extends EntityExtension
{
    public function extendFields(FieldCollection $collection): void
    {
        $collection->add(
            new OneToManyAssociationField(
                'froshMailArchive',
                MailArchiveDefinition::class,
                'salesChannelId'
            )
        );
    }

    public function getDefinitionClass(): string
    {
        return SalesChannelDefinition::class;
    }
}
