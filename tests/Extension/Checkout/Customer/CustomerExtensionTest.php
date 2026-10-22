<?php

declare(strict_types=1);

namespace Frosh\MailArchive\Tests\Extension\Checkout\Customer;

use Frosh\MailArchive\Content\MailArchive\MailArchiveDefinition;
use Frosh\MailArchive\Extension\Checkout\Customer\CustomerExtension;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\CascadeDelete;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\RestrictDelete;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\SetNullOnDelete;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\Framework\Log\Package;

/**
 * @internal
 */
#[Package('framework')]
#[CoversClass(CustomerExtension::class)]
class CustomerExtensionTest extends TestCase
{
    public function testArchiveAssociationDoesNotAffectDeletion(): void
    {
        $fields = new FieldCollection();
        (new CustomerExtension())->extendFields($fields);

        $association = $fields->first();

        static::assertInstanceOf(OneToManyAssociationField::class, $association);
        static::assertSame('froshMailArchive', $association->getPropertyName());
        static::assertSame(MailArchiveDefinition::class, $association->getReferenceClass());
        static::assertSame('customerId', $association->getReferenceField());
        static::assertFalse($association->is(SetNullOnDelete::class));
        static::assertFalse($association->is(CascadeDelete::class));
        static::assertFalse($association->is(RestrictDelete::class));
    }
}
