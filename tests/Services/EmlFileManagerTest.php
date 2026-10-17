<?php

declare(strict_types=1);

namespace Frosh\MailArchive\Tests\Services;

use Frosh\MailArchive\Services\EmlFileManager;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(EmlFileManager::class)]
class EmlFileManagerTest extends TestCase
{
    public function testRoundTripUsesPathDerivedFromUuid(): void
    {
        $manager = new EmlFileManager(new Filesystem(new InMemoryFilesystemAdapter()));
        $id = '018f8dd0865b7f37a99b37e811b91a09';

        $path = $manager->writeFile($id, 'mail content');

        static::assertMatchesRegularExpression('#\Amails/01/8f/8d/' . $id . '\.eml\.(?:gz|zst)\z#', $path);
        static::assertSame('mail content', $manager->getEmlFileAsString($path));
    }

    public function testReadRejectsPathTraversalBeforeAccessingFilesystem(): void
    {
        $filesystem = $this->createMock(FilesystemOperator::class);
        $filesystem->expects($this->never())->method('read');

        $manager = new EmlFileManager($filesystem);

        static::assertFalse($manager->getEmlFileAsString('mails/../../config/jwt/private.pem'));
    }

    public function testWriteRejectsInvalidId(): void
    {
        $manager = new EmlFileManager(new Filesystem(new InMemoryFilesystemAdapter()));

        $this->expectException(\InvalidArgumentException::class);
        $manager->writeFile('../escape', 'mail content');
    }
}
