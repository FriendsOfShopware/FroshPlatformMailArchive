<?php declare(strict_types=1);

namespace Frosh\MailArchive\Services;

use League\Flysystem\FilesystemOperator;
use ZBateson\MailMimeParser\IMessage;
use ZBateson\MailMimeParser\MailMimeParser;

class EmlFileManager
{
    public function __construct(
        private readonly FilesystemOperator $filesystem
    ) {
    }

    /**
     * @return string Path to the eml file
     */
    public function writeFile(string $id, string $content): string
    {
        $content = \gzcompress($content, 9);
        $emlFilePath = 'mails/' . $id . '.eml.gz';

        $this->filesystem->write($emlFilePath, $content);

        return $emlFilePath;
    }

    public function getEmlFileAsString(string $emlFilePath): false|string
    {
        if (!$this->filesystem->fileExists($emlFilePath)) {
            return false;
        }

        return \gzuncompress($this->filesystem->read($emlFilePath));
    }

    public function getEmlAsMessage(string $emlFilePath): false|IMessage
    {
        $emlResource = fopen('php://memory', 'r+b');

        if (!\is_resource($emlResource)) {
            return false;
        }

        $content = $this->getEmlFileContent($emlFilePath);

        if (empty($content)) {
            return false;
        }

        fwrite($emlResource, $content);
        rewind($emlResource);

        $parser = new MailMimeParser();

        return $parser->parse($emlResource, false);
    }
}
