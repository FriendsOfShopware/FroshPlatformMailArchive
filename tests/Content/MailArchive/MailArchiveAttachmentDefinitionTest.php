<?php

declare(strict_types=1);

namespace Frosh\MailArchive\Tests\Content\MailArchive;

use Frosh\MailArchive\Content\MailArchive\MailArchiveAttachmentDefinition;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\DataAbstractionLayer\EntityProtection\WriteProtection;

/**
 * @internal
 */
#[CoversClass(MailArchiveAttachmentDefinition::class)]
class MailArchiveAttachmentDefinitionTest extends TestCase
{
    public function testAttachmentEntityIsNotWriteProtected(): void
    {
        $definition = new MailArchiveAttachmentDefinition();

        static::assertNull($definition->getProtections()->get(WriteProtection::class));
    }
}
