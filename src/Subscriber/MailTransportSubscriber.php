<?php

namespace Frosh\MailArchive\Subscriber;

use Frosh\MailArchive\Services\MailSender;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mailer\Event\FailedMessageEvent;
use Symfony\Component\Mailer\Event\SentMessageEvent;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\RawMessage;

class MailTransportSubscriber implements EventSubscriberInterface
{

    public function __construct(
        private readonly EntityRepository $froshMailArchiveRepository,
    )
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            FailedMessageEvent::class => 'onMessageFailed',
            SentMessageEvent::class => 'onMessageSent'
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
        $context = Context::createDefaultContext();
        $archiveId = $this->getArchiveIdByMessage($message, $context);

        if($archiveId){
            $this->froshMailArchiveRepository->update([[
                'id' => $archiveId,
                'transportState' => $newState
            ]], $context);
        }
    }

    private function getArchiveIdByMessage(RawMessage $message, Context $context): ?string
    {
        if(!($message instanceof Email)) {
            return null;
        }

        $messageIdHeader = $message->getHeaders()->get(MailSender::FROSH_MESSAGE_ID_HEADER);

        if(!$messageIdHeader){
            return null;
        }

        $messageId = $messageIdHeader->getBody();
        $message->getHeaders()->remove(MailSender::FROSH_MESSAGE_ID_HEADER);

        if(!$messageId || !is_string($messageId)){
            return null;
        }

        return $this->froshMailArchiveRepository->searchIds(new Criteria([$messageId]), $context)->firstId();
    }
}
