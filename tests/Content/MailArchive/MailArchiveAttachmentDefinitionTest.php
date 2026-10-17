<?php

declare(strict_types=1);

namespace Frosh\MailArchive\Tests\Content\MailArchive;

use Frosh\MailArchive\Content\MailArchive\MailArchiveAttachmentDefinition;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityProtection\WriteProtection;

/**
 * @internal
 */
#[CoversClass(MailArchiveAttachmentDefinition::class)]
class MailArchiveAttachmentDefinitionTest extends TestCase
{
    public function testAttachmentEntityIsSystemWriteProtected(): void
    {
        $definition = new MailArchiveAttachmentDefinition();
        $writeProtection = $definition->getProtections()->get(WriteProtection::class);

        static::assertInstanceOf(WriteProtection::class, $writeProtection);
        static::assertSame([Context::SYSTEM_SCOPE], $writeProtection->getAllowedScopes());
    }
}
