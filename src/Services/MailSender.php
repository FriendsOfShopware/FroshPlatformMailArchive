<?php

declare(strict_types=1);

namespace Frosh\MailArchive\Services;

use Frosh\MailArchive\Content\MailArchive\MailArchiveEntity;
use Frosh\MailArchive\Content\MailArchive\MailArchiveException;
use Shopware\Core\Content\Mail\Service\AbstractMailSender;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\OrFilter;
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

    public const FROSH_MESSAGE_ID_HEADER = 'Frosh-Message-ID';

    public function __construct(
        private readonly AbstractMailSender $mailSender,
        private readonly RequestStack       $requestStack,
        private readonly EntityRepository   $froshMailArchiveRepository,
        private readonly EntityRepository   $customerRepository,
        private readonly EmlFileManager     $emlFileManager
    )
    {
    }

    public function send(Email $email, ?Envelope $envelope = null): void
    {
        $id = Uuid::randomHex();
        $email->getHeaders()->remove(self::FROSH_MESSAGE_ID_HEADER);
        $email->getHeaders()->addHeader(self::FROSH_MESSAGE_ID_HEADER, $id);

        // save the mail first, to make sure it exists in the database when we want to update its state
        $this->saveMail($id, $email);
        $this->mailSender->send($email, $envelope);

    }

    public function getDecorated(): AbstractMailSender
    {
        return $this->mailSender;
    }

    private function saveMail(string $id, Email $message): void
    {
        $emlPath = $this->emlFileManager->writeFile($id, $message->toString());

        $attachments = [];

        foreach ($message->getAttachments() as $attachment) {
            $attachments[] = [
                'fileName' => $attachment->getFilename(),
                'contentType' => $attachment->getContentType(),
                'fileSize' => \strlen($attachment->bodyToString()),
            ];
        }

        $context = Context::createDefaultContext();
        $parentId = $this->getParentId($context);

        if ($parentId !== null) {
            $criteria = (new Criteria())->addFilter(
                new OrFilter([
                    new EqualsFilter('id', $parentId),
                    new EqualsFilter('parentId', $parentId),
                ])
            );
            /** @var array<string> $ids */
            $ids = $this->froshMailArchiveRepository->searchIds($criteria, $context)->getIds();

            $payload = array_map(fn ($id) => [
                'id' => (string) $id,
                'historyLastMail' => false,
            ], $ids);

            $this->froshMailArchiveRepository->update($payload, $context);
        }

        $payload = [
            'id' => $id,
            'sender' => [$message->getFrom()[0]->getAddress() => $message->getFrom()[0]->getName()],
            'receiver' => $this->convertAddress($message->getTo()),
            'subject' => $message->getSubject(),
            'plainText' => nl2br((string) $message->getTextBody()),
            'htmlText' => $message->getHtmlBody(),
            'emlPath' => $emlPath,
            'salesChannelId' => $this->getCurrentSalesChannelId(),
            'customerId' => $this->getCustomerIdByMail($message->getTo()),
            'attachments' => $attachments,
            'parentId' => $parentId,
            'historyLastMail' => true,
            'transportState' => self::TRANSPORT_STATE_PENDING,
        ];

        $this->froshMailArchiveRepository->create([$payload], $context);
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

    private function getParentId(Context $context): ?string
    {
        $request = $this->requestStack->getMainRequest();
        if ($request === null) {
            return null;
        }

        $route = $request->attributes->get('_route');
        if ($route !== 'api.action.frosh-mail-archive.resend-mail') {
            return null;
        }

        $mailId = $request->request->get('mailId');

        if (!\is_string($mailId)) {
            throw MailArchiveException::parameterMissing('mailId in request');
        }

        /** @var MailArchiveEntity|null $mail */
        $mail = $this->froshMailArchiveRepository->search(new Criteria([$mailId]), $context)->first();

        // In case the Mail is a resend, we want to save the original mail id
        return $mail?->getParentId() ?? $mailId;
    }

    /**
     * @param Address[] $to
     */
    private function getCustomerIdByMail(array $to): ?string
    {
        $addresses = \array_map(fn (Address $mail) => $mail->getAddress(), $to);

        $criteria = (new Criteria())
            ->addFilter(new EqualsAnyFilter('email', $addresses));

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
}
