<?php

declare(strict_types=1);

namespace Frosh\MailArchive\Services;

use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

final class ResendRecipientOverride
{
    /**
     * @param non-empty-list<Address> $recipients
     */
    public function replace(Email $email, array $recipients): void
    {
        foreach (['To', 'Cc', 'Bcc', 'Resent-To', 'Resent-Cc', 'Resent-Bcc'] as $header) {
            $email->getHeaders()->remove($header);
        }

        $email->to(...$recipients);
    }
}
