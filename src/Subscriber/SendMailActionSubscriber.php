<?php

namespace Frosh\MailArchive\Subscriber;

use Shopware\Core\Content\Flow\Events\FlowSendMailActionEvent;
use Shopware\Core\Framework\Event\CustomerAware;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SendMailActionSubscriber implements EventSubscriberInterface
{

    public static function getSubscribedEvents(): array
    {
        return [
            FlowSendMailActionEvent::class => 'onSendMailAction'
        ];
    }

    public function onSendMailAction(FlowSendMailActionEvent $e): void
    {
        $flow = $e->getStorableFlow();
        $customerId = $flow->getData(CustomerAware::CUSTOMER_ID);

        $e->getDataBag()->set('customerId', $customerId);
    }
}
