<?php

declare(strict_types=1);

namespace Frosh\MailArchive\Services;

use League\Flysystem\FilesystemOperator;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use ZBateson\MailMimeParser\IMessage;
use ZBateson\MailMimeParser\MailMimeParser;

class EmlFileManager
{
    private const COMPRESSION_EXT_GZIP = 'gz';
    private const COMPRESSION_EXT_ZSTD = 'zst';

    public function __construct(
        #[Autowire(service: 'frosh_platform_mail_archive.filesystem.private')]
        private readonly FilesystemOperator $filesystem,
    ) {
    }

    /**
     * @return string Path to the eml file
     */
    public function writeFile(string $id, string $content): string
    {
        if (!Uuid::isValid($id)) {
            throw new \InvalidArgumentException('The mail archive ID must be a valid UUID.');
        }

        if (\function_exists('zstd_compress')) {
            $content = \zstd_compress($content);
            $extension = '.' . self::COMPRESSION_EXT_ZSTD;
        } else {
            $content = \gzcompress($content, 9);
            $extension = '.' . self::COMPRESSION_EXT_GZIP;
        }

        if ($content === false) {
            throw new \RuntimeException('Cannot compress eml file');
        }

        $folderParts = \array_slice(\str_split($id, 2), 0, 3);

        $emlFilePath = 'mails/' . \implode('/', $folderParts) . '/' . $id . '.eml' . $extension;

        $this->filesystem->write($emlFilePath, $content);

        return $emlFilePath;
    }

    public function getEmlFileAsString(string $emlFilePath): false|string
    {
        if (!$this->isValidEmlPath($emlFilePath)) {
            return false;
        }

        try {
            $extension = \pathinfo($emlFilePath, \PATHINFO_EXTENSION);

            $content = $this->filesystem->read($emlFilePath);

            if ($extension === self::COMPRESSION_EXT_ZSTD) {
                return \zstd_uncompress($content);
            }

            return \gzuncompress($content);
        } catch (\Throwable) {
            return false;
        }
    }

    public function getEmlAsMessage(string $emlFilePath): false|IMessage
    {
        $emlResource = fopen('php://memory', 'r+b');

        if (!\is_resource($emlResource)) {
            return false;
        }

        $content = $this->getEmlFileAsString($emlFilePath);

        if ($content === '' || $content === '0' || $content === false) {
            return false;
        }

        fwrite($emlResource, $content);
        rewind($emlResource);

        return (new MailMimeParser())->parse($emlResource, false);
    }

    public function deleteEmlFile(string $emlFilePath): void
    {
        if (!$this->isValidEmlPath($emlFilePath)) {
            return;
        }

        if (!$this->filesystem->fileExists($emlFilePath)) {
            return;
        }

        $this->filesystem->delete($emlFilePath);
    }

    private function isValidEmlPath(string $emlFilePath): bool
    {
        $matches = [];
        $matchesPath = preg_match(
            '#\Amails/(?:(?<first>[0-9a-f]{2})/(?<second>[0-9a-f]{2})/(?<third>[0-9a-f]{2})/)?(?<id>[0-9a-f]{32})\.eml\.(?:gz|zst)\z#i',
            $emlFilePath,
            $matches,
        );

        if ($matchesPath !== 1) {
            return false;
        }

        if ($matches['first'] === '') {
            return true;
        }

        return \strtolower($matches['first'] . $matches['second'] . $matches['third']) === \substr(\strtolower($matches['id']), 0, 6);
    }
}
