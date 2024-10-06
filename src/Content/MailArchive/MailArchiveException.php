<?php

declare(strict_types=1);

namespace Frosh\MailArchive\Content\MailArchive;

use Shopware\Core\Framework\HttpException;
use Symfony\Component\HttpFoundation\Response;

class MailArchiveException extends HttpException
{
    public const NOT_FOUND_CODE = 'MAIL_ARCHIVE__NOT_FOUND';
    public const MISSING_PARAMETER_CODE = 'MAIL_ARCHIVE__MISSING_PARAMETER';
    public const INVALID_UUID_CODE = 'MAIL_ARCHIVE__PARAMETER_INVALID_UUID';
    public const UNREADABLE_EML_CODE = 'MAIL_ARCHIVE__UNREADABLE_EML';

    public static function notFound(): self
    {
        return new self(
            Response::HTTP_NOT_FOUND,
            self::NOT_FOUND_CODE,
            'Cannot find mail in archive',
        );
    }

    public static function parameterMissing(string $parameter): self
    {
        return new self(
            Response::HTTP_BAD_REQUEST,
            self::MISSING_PARAMETER_CODE,
            'Parameter "{{parameter}}" is missing',
            ['parameter' => $parameter],
        );
    }

    public static function parameterInvalidUuid(string $parameter): self
    {
        return new self(
            Response::HTTP_BAD_REQUEST,
            self::INVALID_UUID_CODE,
            'Parameter "{{parameter}}" is not a valid UUID',
            ['parameter' => $parameter]
        );
    }

    public static function unreadableEml(string $path): self
    {
        return new self(
            Response::HTTP_INTERNAL_SERVER_ERROR,
            self::UNREADABLE_EML_CODE,
            'Cannot read eml file at "{{path}}" or file is empty',
            ['path' => $path],
        );
    }
}
