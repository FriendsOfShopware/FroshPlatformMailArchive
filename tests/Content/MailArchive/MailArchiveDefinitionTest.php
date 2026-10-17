<?php

declare(strict_types=1);

namespace Frosh\MailArchive\Tests\Content\MailArchive;

use Frosh\MailArchive\Content\MailArchive\MailArchiveDefinition;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Field\AssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\WriteProtected;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

/**
 * @internal
 */
#[CoversClass(MailArchiveDefinition::class)]
class MailArchiveDefinitionTest extends TestCase
{
    public function testAllStoredArchiveFieldsAreSystemWriteProtected(): void
    {
        $definition = (new \ReflectionClass(TestableMailArchiveDefinition::class))->newInstanceWithoutConstructor();

        foreach ($definition->definedFields() as $field) {
            if ($field instanceof AssociationField) {
                continue;
            }

            $writeProtected = $field->getFlag(WriteProtected::class);
            static::assertInstanceOf(WriteProtected::class, $writeProtected, $field->getPropertyName());
            static::assertTrue($writeProtected->isAllowed(Context::SYSTEM_SCOPE), $field->getPropertyName());
        }
    }
}

class TestableMailArchiveDefinition extends MailArchiveDefinition
{
    public function definedFields(): FieldCollection
    {
        return $this->defineFields();
    }
}
