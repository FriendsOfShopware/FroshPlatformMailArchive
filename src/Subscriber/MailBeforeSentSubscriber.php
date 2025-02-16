<?php

namespace Frosh\MailArchive\Subscriber;

use Frosh\MailArchive\Services\MailSender;
use Shopware\Core\Content\MailTemplate\Service\Event\MailBeforeSentEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MailBeforeSentSubscriber implements EventSubscriberInterface
{

    public static function getSubscribedEvents(): array
    {
        return [
            MailBeforeSentEvent::class => 'onMailBeforeSent'
        ];
    }

    public function onMailBeforeSent(MailBeforeSentEvent $e): void
    {
        $customerId = $e->getData()['customerId'] ?? null;

        if(!$customerId){
            return;
        }

        $e->getMessage()->getHeaders()->addTextHeader(MailSender::FROSH_CUSTOMER_ID_HEADER, $customerId);
    }
}
