<?php

declare(strict_types=1);

namespace Frosh\MailArchive\Content\MailArchive;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class MailArchiveAttachmentEntity extends Entity
{
    use EntityIdTrait;

    protected string $mailArchiveId;

    protected ?MailArchiveEntity $mailArchive;

    protected string $fileName;

    protected string $contentType;

    protected int $fileSize;

    public function getMailArchiveId(): string
    {
        return $this->mailArchiveId;
    }

    public function setMailArchiveId(string $mailArchiveId): void
    {
        $this->mailArchiveId = $mailArchiveId;
    }

    public function getMailArchive(): ?MailArchiveEntity
    {
        return $this->mailArchive;
    }

    public function setMailArchive(?MailArchiveEntity $mailArchive): void
    {
        $this->mailArchive = $mailArchive;
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

    public function setFileName(string $fileName): void
    {
        $this->fileName = $fileName;
    }

    public function getContentType(): string
    {
        return $this->contentType;
    }

    public function setContentType(string $contentType): void
    {
        $this->contentType = $contentType;
    }

    public function getFileSize(): int
    {
        return $this->fileSize;
    }

    public function setFileSize(int $fileSize): void
    {
        $this->fileSize = $fileSize;
    }
}
