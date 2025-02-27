<?php

declare(strict_types=1);

namespace Frosh\MailArchive\Services;

use Frosh\MailArchive\Content\MailArchive\MailArchiveEntity;
use Frosh\MailArchive\Content\MailArchive\MailArchiveException;
use Shopware\Core\Checkout\Customer\CustomerCollection;
use Shopware\Core\Content\Mail\Service\AbstractMailSender;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\PlatformRequest;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

#[AsDecorator(decorates: \Shopware\Core\Content\Mail\Service\MailSender::class)]
class MailSender extends AbstractMailSender
{
    public const TRANSPORT_STATE_PENDING = 'pending';
    public const TRANSPORT_STATE_FAILED = 'failed';
    public const TRANSPORT_STATE_SENT = 'sent';
    public const TRANSPORT_STATE_RESENT = 'resent';

    public const FROSH_MESSAGE_ID_HEADER = 'Frosh-Message-ID';
    public const FROSH_CUSTOMER_ID_HEADER = 'X-Frosh-Customer-ID';
    public const FROSH_ORDER_ID_HEADER = 'X-Frosh-Order-ID';
    public const FROSH_FLOW_ID_HEADER = 'X-Frosh-Flow-ID';

    /**
     * @param EntityRepository<EntityCollection<MailArchiveEntity>> $froshMailArchiveRepository
     * @param EntityRepository<CustomerCollection> $customerRepository
     */
    public function __construct(
        private readonly AbstractMailSender $mailSender,
        private readonly RequestStack       $requestStack,
        private readonly EntityRepository   $froshMailArchiveRepository,
        private readonly EntityRepository   $customerRepository,
        private readonly EmlFileManager     $emlFileManager,
    ) {}

    public function send(Email $email, ?Envelope $envelope = null): void
    {
        $id = Uuid::randomHex();
        $email->getHeaders()->remove(self::FROSH_MESSAGE_ID_HEADER);
        $email->getHeaders()->addHeader(self::FROSH_MESSAGE_ID_HEADER, $id);

        $metadata = $this->getMailMetadata($email);

        // save the mail first, to make sure it exists in the database when we want to update its state
        $this->saveMail($id, $email, $metadata);
        $this->mailSender->send($email, $envelope);

    }

    public function getDecorated(): AbstractMailSender
    {
        return $this->mailSender;
    }

    /**
     * @param array<string,string|null> $metadata
     */
    private function saveMail(string $id, Email $message, array $metadata): void
    {
        $emlPath = $this->emlFileManager->writeFile($id, $message->toString());

        $context = Context::createDefaultContext();
        $this->froshMailArchiveRepository->create([
            [
                'id' => $id,
                'sender' => [$message->getFrom()[0]->getAddress() => $message->getFrom()[0]->getName()],
                'receiver' => $this->convertAddress($message->getTo()),
                'subject' => $message->getSubject(),
                'cc' => $message->getCc(),
                'bcc' => $message->getBcc(),
                'plainText' => nl2br((string) $message->getTextBody()),
                'htmlText' => $message->getHtmlBody(),
                'emlPath' => $emlPath,
                'salesChannelId' => $this->getCurrentSalesChannelId(),
                'customerId' => $metadata['customerId'] ?? $this->getCustomerIdByMail($message->getTo()),
                'sourceMailId' => $this->getSourceMailId($context),
                'transportState' => self::TRANSPORT_STATE_PENDING,
                'orderId' => $metadata['orderId'],
                'flowId' => $metadata['flowId'],
            ],
        ], $context);
    }

    private function getCurrentSalesChannelId(): ?string
    {
        if ($this->requestStack->getMainRequest() === null) {
            return null;
        }

        $salesChannelId = $this->requestStack->getMainRequest()->attributes->get(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_ID);
        if (!\is_string($salesChannelId)) {
            return null;
        }

        return $salesChannelId;
    }

    private function getSourceMailId(Context $context): ?string
    {
        $request = $this->requestStack->getMainRequest();
        if ($request === null) {
            return null;
        }

        $route = $request->attributes->get('_route');
        if ($route !== 'api.action.frosh-mail-archive.resend-mail') {
            return null;
        }

        $sourceMailId = $request->request->get('mailId');

        if (!\is_string($sourceMailId)) {
            throw MailArchiveException::parameterMissing('mailId in request');
        }

        /** @var MailArchiveEntity|null $sourceMail */
        $sourceMail = $this->froshMailArchiveRepository->search(new Criteria([$sourceMailId]), $context)->first();

        // In case the source Mail is a resend, we want to save the original source mail id
        return $sourceMail?->getSourceMailId() ?? $sourceMailId;
    }

    /**
     * @param Address[] $to
     */
    private function getCustomerIdByMail(array $to): ?string
    {
        $criteria = new Criteria();

        $addresses = \array_map(function (Address $mail) {
            return $mail->getAddress();
        }, $to);

        $criteria->addFilter(new EqualsAnyFilter('email', $addresses));

        return $this->customerRepository->searchIds($criteria, Context::createDefaultContext())->firstId();
    }

    /**
     * @param Address[] $addresses
     * @return array<string, string>
     */
    private function convertAddress(array $addresses): array
    {
        $list = [];

        foreach ($addresses as $address) {
            $list[$address->getAddress()] = $address->getName();
        }

        return $list;
    }

    /**
     * @return array<string, string|null>
     */
    private function getMailMetadata(Email $email): array
    {
        $customerIdHeader = $email->getHeaders()->get(self::FROSH_CUSTOMER_ID_HEADER);
        $email->getHeaders()->remove(self::FROSH_CUSTOMER_ID_HEADER);

        $orderIdHeader = $email->getHeaders()->get(self::FROSH_ORDER_ID_HEADER);
        $email->getHeaders()->remove(self::FROSH_ORDER_ID_HEADER);

        $flowIdHeader = $email->getHeaders()->get(self::FROSH_FLOW_ID_HEADER);
        $email->getHeaders()->remove(self::FROSH_FLOW_ID_HEADER);

        return [
            'customerId' => $customerIdHeader?->getBodyAsString() ?: null,
            'orderId' => $orderIdHeader?->getBodyAsString() ?: null,
            'flowId' => $flowIdHeader?->getBodyAsString() ?: null,
        ];
    }
}
