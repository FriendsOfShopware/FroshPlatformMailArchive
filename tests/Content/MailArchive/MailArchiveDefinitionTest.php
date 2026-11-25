<?php

declare(strict_types=1);

namespace Frosh\MailArchive\Tests\Content\MailArchive;

use Frosh\MailArchive\Content\MailArchive\MailArchiveDefinition;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\DataAbstractionLayer\EntityProtection\WriteProtection;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\AllowHtml;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

/**
 * @internal
 */
#[CoversClass(MailArchiveDefinition::class)]
class MailArchiveDefinitionTest extends TestCase
{
    public function testArchiveEntityIsNotWriteProtected(): void
    {
        $definition = new MailArchiveDefinition();

        static::assertNull($definition->getProtections()->get(WriteProtection::class));
    }

    public function testHtmlIsArchivedAsItWasSent(): void
    {
        $fields = (new \ReflectionMethod(MailArchiveDefinition::class, 'defineFields'))->invoke(new MailArchiveDefinition());

        static::assertInstanceOf(FieldCollection::class, $fields);

        $flags = [];
        foreach ($fields as $field) {
            $flags[$field->getPropertyName()] = $field->getFlag(AllowHtml::class);
        }

        static::assertInstanceOf(AllowHtml::class, $flags['htmlText']);
        static::assertFalse($flags['htmlText']->isSanitized());
        static::assertInstanceOf(AllowHtml::class, $flags['plainText']);
        static::assertTrue($flags['plainText']->isSanitized());
    }
}
