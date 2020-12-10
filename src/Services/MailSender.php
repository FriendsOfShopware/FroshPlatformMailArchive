<?php

namespace Frosh\MailArchive\Services;

use Shopware\Core\Content\MailTemplate\Service\MailSender as ShopwareMailSender;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\HttpFoundation\RequestStack;

class MailSender extends ShopwareMailSender
{
    /**
     * @var MailSender
     */
    private $mailSender;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var EntityRepositoryInterface
     */
    private $mailArchiveRepository;

    /**
     * @var EntityRepositoryInterface
     */
    private $customerRepository;

    public function __construct(
        ShopwareMailSender $mailSender,
        RequestStack $requestStack,
        EntityRepositoryInterface $mailArchiveRepository,
        EntityRepositoryInterface $customerRepository
    )
    {
        $this->mailSender = $mailSender;
        $this->requestStack = $requestStack;
        $this->mailArchiveRepository = $mailArchiveRepository;
        $this->customerRepository = $customerRepository;
    }

    public function send(\Swift_Message $message): void
    {
        $this->saveMail($message);

        $this->mailSender->send($message);
    }

    private function saveMail(\Swift_Message $message): void
    {
        $plain = null;
        $html = null;

        foreach ($message->getChildren() as $child) {
            if ($child->getBodyContentType() === 'text/html') {
                $html = $child->getBody();
                continue;
            }
            if ($child->getBodyContentType() === 'text/plain') {
                $plain = $child->getBody();
            }
        }

        $this->mailArchiveRepository->create([
            [
                'id' => Uuid::randomHex(),
                'sender' => $message->getFrom(),
                'receiver' => $message->getTo(),
                'subject' => $message->getSubject(),
                'plainText' => nl2br($plain),
                'htmlText' => $html,
                'eml' => $message->toString(),
                'salesChannelId' => $this->getCurrentSalesChannelId(),
                'customerId' => $this->getCustomerIdByMail(array_keys($message->getTo()))
            ]
        ], Context::createDefaultContext());
    }

    private function getCurrentSalesChannelId(): ?string
    {
        if ($this->requestStack->getMasterRequest() === null) {
            return null;
        }

        return $this->requestStack->getMasterRequest()->attributes->get('sw-sales-channel-id');
    }

    private function getCustomerIdByMail(array $mails)
    {
        $criteria = new Criteria();

        $criteria->addFilter(new EqualsAnyFilter('email', $mails));
        return $this->customerRepository->searchIds($criteria, Context::createDefaultContext())->firstId();
    }
}
