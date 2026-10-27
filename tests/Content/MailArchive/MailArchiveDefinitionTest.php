<?php

declare(strict_types=1);

namespace Frosh\MailArchive\Tests\Content\MailArchive;

use Frosh\MailArchive\Content\MailArchive\MailArchiveDefinition;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\DataAbstractionLayer\EntityProtection\WriteProtection;

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
}
