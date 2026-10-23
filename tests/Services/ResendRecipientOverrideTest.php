<?php

declare(strict_types=1);

namespace Frosh\MailArchive\Tests\Services;

use Frosh\MailArchive\Services\ResendRecipientOverride;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

/**
 * @internal
 */
#[CoversClass(ResendRecipientOverride::class)]
class ResendRecipientOverrideTest extends TestCase
{
    public function testOverrideRemovesEveryOriginalEnvelopeRecipient(): void
    {
        $email = (new Email())
            ->from('sender@example.com')
            ->to('original@example.com')
            ->cc('copy@example.com')
            ->bcc('blind-copy@example.com')
            ->subject('Archived mail')
            ->text('Content');
        $email->getHeaders()->addMailboxListHeader('Resent-To', [new Address('resent@example.com')]);
        $email->getHeaders()->addMailboxListHeader('Resent-Cc', [new Address('resent-copy@example.com')]);
        $email->getHeaders()->addMailboxListHeader('Resent-Bcc', [new Address('resent-blind-copy@example.com')]);

        (new ResendRecipientOverride())->replace($email, [new Address('replacement@example.com')]);

        $recipients = array_map(
            static fn (Address $address): string => $address->getAddress(),
            Envelope::create($email)->getRecipients(),
        );

        static::assertSame(['replacement@example.com'], $recipients);
        static::assertSame([], $email->getCc());
        static::assertSame([], $email->getBcc());
        static::assertFalse($email->getHeaders()->has('Resent-To'));
        static::assertFalse($email->getHeaders()->has('Resent-Cc'));
        static::assertFalse($email->getHeaders()->has('Resent-Bcc'));
    }
}
