<?php

declare(strict_types=1);

namespace Frosh\MailArchive\Tests\Content\MailArchive;

use Frosh\MailArchive\Content\MailArchive\MailArchiveAttachmentDefinition;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Field\AssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\WriteProtected;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

/**
 * @internal
 */
#[CoversClass(MailArchiveAttachmentDefinition::class)]
class MailArchiveAttachmentDefinitionTest extends TestCase
{
    public function testAllStoredAttachmentFieldsAreSystemWriteProtected(): void
    {
        $definition = (new \ReflectionClass(TestableMailArchiveAttachmentDefinition::class))->newInstanceWithoutConstructor();

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

class TestableMailArchiveAttachmentDefinition extends MailArchiveAttachmentDefinition
{
    public function definedFields(): FieldCollection
    {
        return $this->defineFields();
    }
}
