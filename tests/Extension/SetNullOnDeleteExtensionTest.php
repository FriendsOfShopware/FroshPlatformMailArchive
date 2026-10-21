<?php

declare(strict_types=1);

namespace Frosh\MailArchive\Tests\Extension;

use Frosh\MailArchive\Extension\Checkout\Customer\CustomerExtension;
use Frosh\MailArchive\Extension\Checkout\Order\OrderExtension;
use Frosh\MailArchive\Extension\Content\Flow\FlowExtension;
use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\DataAbstractionLayer\EntityExtension;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\SetNullOnDelete;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

/**
 * @internal
 */
#[CoversClass(CustomerExtension::class)]
#[CoversClass(OrderExtension::class)]
#[CoversClass(FlowExtension::class)]
class SetNullOnDeleteExtensionTest extends TestCase
{
    /**
     * @param class-string<EntityExtension> $extensionClass
     */
    #[DataProvider('associationProvider')]
    public function testArchiveAssociationIsNulledByDatabaseConstraint(string $extensionClass, string $referenceField): void
    {
        $extension = new $extensionClass();
        $collection = new FieldCollection();
        $extension->extendFields($collection);

        $field = $collection->get('froshMailArchive');

        static::assertInstanceOf(OneToManyAssociationField::class, $field);
        static::assertSame($referenceField, $field->getReferenceField());

        $flag = $field->getFlag(SetNullOnDelete::class);
        static::assertInstanceOf(SetNullOnDelete::class, $flag);
        static::assertTrue($flag->isEnforcedByConstraint());
    }

    /**
     * @return Generator<string, array{class-string<EntityExtension>, string}>
     */
    public static function associationProvider(): Generator
    {
        yield 'customer' => [CustomerExtension::class, 'customerId'];
        yield 'order' => [OrderExtension::class, 'order_id'];
        yield 'flow' => [FlowExtension::class, 'flow_id'];
    }
}
