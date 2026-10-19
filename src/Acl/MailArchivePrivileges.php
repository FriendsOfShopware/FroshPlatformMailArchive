<?php

declare(strict_types=1);

namespace Frosh\MailArchive\Acl;

final class MailArchivePrivileges
{
    public const READ = 'frosh_mail_archive:read';
    public const ATTACHMENT_READ = 'frosh_mail_archive_attachment:read';
    public const RESEND = 'frosh_mail_archive:resend';
}
