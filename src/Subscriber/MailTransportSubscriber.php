<?php

declare(strict_types=1);

namespace Frosh\MailArchive\Subscriber;

use Frosh\MailArchive\Content\MailArchive\MailArchiveEntity;
use Frosh\MailArchive\Services\EmlFileManager;
use Frosh\MailArchive\Services\MailSender;
use Shopware\Core\Framework\Api\Context\SystemSource;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mailer\Event\FailedMessageEvent;
use Symfony\Component\Mailer\Event\SentMessageEvent;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\RawMessage;

readonly class MailTransportSubscriber implements EventSubscriberInterface
{
    /**
     * @param EntityRepository<EntityCollection<MailArchiveEntity>> $froshMailArchiveRepository
     */
    public function __construct(
        private EntityRepository $froshMailArchiveRepository,
        private EmlFileManager $emlFileManager,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            FailedMessageEvent::class => 'onMessageFailed',
            SentMessageEvent::class => 'onMessageSent',
        ];
    }

    public function onMessageFailed(FailedMessageEvent $e): void
    {
        $message = $e->getMessage();
        $this->updateArchiveState($message, MailSender::TRANSPORT_STATE_FAILED);
    }

    public function onMessageSent(SentMessageEvent $event): void
    {
        $message = $event->getMessage()->getOriginalMessage();
        $this->updateArchiveState($message, MailSender::TRANSPORT_STATE_SENT);
    }

    private function updateArchiveState(RawMessage $message, string $newState): void
    {
        if (!$message instanceof Email) {
            return;
        }

        $context = new Context(new SystemSource());
        $archiveId = $this->getArchiveIdByMessage($message);

        if (!$archiveId) {
            return;
        }

        $this->emlFileManager->writeFile($archiveId, $message->toString());

        $attachments = $this->getAttachments($message);
        $this->froshMailArchiveRepository->update([[
            'id' => $archiveId,
            'transportState' => $newState,
            'attachments' => $attachments,
        ]], $context);
    }

    /**
     * @return array<array{'fileName': string, 'contentType': string, 'fileSize': int}>
     */
    private function getAttachments(Email $message): array
    {
        $attachments = $message->getAttachments();

        return array_map(static fn(DataPart $attachment) => [
            'fileName' => $attachment->getFilename() ?? 'attachment',
            'contentType' => $attachment->getContentType(),
            'fileSize' => \strlen($attachment->getBody()),
        ], $attachments);
    }

    private function getArchiveIdByMessage(Email $message): ?string
    {
        $messageId = $message->getHeaders()->get(MailSender::FROSH_MESSAGE_ID_HEADER)?->getBody();

        if (\is_string($messageId)) {
            return $messageId;
        }

        return null;
    }
}
