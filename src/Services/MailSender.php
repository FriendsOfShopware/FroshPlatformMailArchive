<?php

namespace Frosh\MailArchive\Services;

use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Content\Mail\Service\AbstractMailSender;
use Shopware\Core\Content\Mail\Service\AbstractMailService;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class MailSender extends AbstractMailSender
{
    private AbstractMailSender $mailSender;

    private RequestStack $requestStack;

    private EntityRepositoryInterface $mailArchiveRepository;

    private EntityRepositoryInterface $customerRepository;

    public function __construct(
        AbstractMailSender $mailSender,
        RequestStack $requestStack,
        EntityRepositoryInterface $mailArchiveRepository,
        EntityRepositoryInterface $customerRepository
    ) {
        $this->mailSender = $mailSender;
        $this->requestStack = $requestStack;
        $this->mailArchiveRepository = $mailArchiveRepository;
        $this->customerRepository = $customerRepository;
    }

    public function send(Email $email, ?Envelope $envelope = null): void
    {
        // let first send the mail itself, to see if it was really sent or entered error state
        $this->mailSender->send($email, $envelope);

        $this->saveMail($email);
    }

    private function saveMail(Email $message): void
    {
        $this->mailArchiveRepository->create([
            [
                'id' => Uuid::randomHex(),
                'sender' => [$message->getFrom()[0]->getAddress() => $message->getFrom()[0]->getName()],
                'receiver' => $this->convertAddress($message->getTo()),
                'subject' => $message->getSubject(),
                'plainText' => nl2br($message->getTextBody()),
                'htmlText' => $message->getHtmlBody(),
                'eml' => $message->toString(),
                'salesChannelId' => $this->getCurrentSalesChannelId(),
                'customerId' => $this->getCustomerIdByMail(array_keys($message->getTo()))
            ]
        ], Context::createDefaultContext());
    }

    private function getCurrentSalesChannelId(): ?string
    {
        if ($this->requestStack->getMainRequest() === null) {
            return null;
        }

        return $this->requestStack->getMainRequest()->attributes->get('sw-sales-channel-id');
    }

    private function getCustomerIdByMail(array $mails): ?string
    {
        $criteria = new Criteria();

        $criteria->addFilter(new EqualsAnyFilter('email', $mails));
        return $this->customerRepository->searchIds($criteria, Context::createDefaultContext())->firstId();
    }

    public function getDecorated(): AbstractMailSender
    {
        return $this->mailSender;
    }

    /**
     * @param Address[] $addresses
     * @return array
     */
    private function convertAddress(array $addresses): array
    {
        $list = [];

        foreach ($addresses as $address) {
            $list[$address->getAddress()] = $address->getName();
        }

        return $list;
    }
}
