<?php declare(strict_types=1);

namespace Frosh\MailArchive\Subscriber;

use Frosh\MailArchive\Services\MailSender;
use Shopware\Core\Content\MailTemplate\Service\Event\MailBeforeSentEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MailBeforeSentSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            MailBeforeSentEvent::class => 'onMailBeforeSent',
        ];
    }

    public function onMailBeforeSent(MailBeforeSentEvent $e): void
    {
        /** @var string $customerId */
        $customerId = $e->getData()['customerId'] ?? '';

        /** @var string $orderId */
        $orderId = $e->getData()['orderId'] ?? '';

        /** @var string $flowId */
        $flowId = $e->getData()['flowId'] ?? '';

        $e->getMessage()->getHeaders()->addTextHeader(MailSender::FROSH_CUSTOMER_ID_HEADER, $customerId);
        $e->getMessage()->getHeaders()->addTextHeader(MailSender::FROSH_ORDER_ID_HEADER, $orderId);
        $e->getMessage()->getHeaders()->addTextHeader(MailSender::FROSH_FLOW_ID_HEADER, $flowId);
    }
}
