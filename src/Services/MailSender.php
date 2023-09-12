<?php declare(strict_types=1);

namespace Frosh\MailArchive\Services;

use Shopware\Core\Content\Mail\Service\AbstractMailSender;
use Shopware\Core\Content\MailTemplate\Exception\MailTransportFailedException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

#[AsDecorator(decorates: \Shopware\Core\Content\Mail\Service\MailSender::class)]
class MailSender extends AbstractMailSender
{
    public function __construct(
        private readonly AbstractMailSender $mailSender,
        private readonly RequestStack $requestStack,
        private readonly EntityRepository $froshMailArchiveRepository,
        private readonly EntityRepository $customerRepository,
        private readonly EmlFileManager $emlFileManager
    ) {
    }

    public function send(Email $email, ?Envelope $envelope = null): void
    {
        // let first send the mail itself, to see if it was really sent or entered error state
        try {
            $this->mailSender->send($email, $envelope);
            $this->saveMail($email);
        }catch(MailTransportFailedException $e){
            $this->saveMail($email, true);
            throw $e;
        }
    }

    public function getDecorated(): AbstractMailSender
    {
        return $this->mailSender;
    }

    private function saveMail(Email $message, bool $transportFailed = false): void
    {
        $id = Uuid::randomHex();

        $emlPath = $this->emlFileManager->writeFile($id, $message->toString());

        $attachments = [];

        foreach ($message->getAttachments() as $attachment) {
            $attachments[] = [
                'fileName' => $attachment->getFilename(),
                'contentType' => $attachment->getContentType(),
                'fileSize' => \strlen($attachment->bodyToString()),
            ];
        }

        $this->froshMailArchiveRepository->create([
            [
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
                'transportFailed' => $transportFailed,
            ],
        ], Context::createDefaultContext());
    }

    private function getCurrentSalesChannelId(): ?string
    {
        if ($this->requestStack->getMainRequest() === null) {
            return null;
        }

        $salesChannelId = $this->requestStack->getMainRequest()->attributes->get('sw-sales-channel-id');
        if (!\is_string($salesChannelId)) {
            return null;
        }

        return $salesChannelId;
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
