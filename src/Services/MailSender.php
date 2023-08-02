<?php declare(strict_types=1);

namespace Frosh\MailArchive\Services;

use Shopware\Core\Content\Mail\Service\AbstractMailSender;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class MailSender extends AbstractMailSender
{
    private readonly EntityRepository $mailArchiveRepository;

    private readonly EntityRepository $customerRepository;

    public function __construct(
        private readonly AbstractMailSender $mailSender,
        private readonly RequestStack $requestStack,
        EntityRepository $mailArchiveRepository,
        EntityRepository $customerRepository,
        private readonly EmlFileManager $emlFileManager
    ) {
        $this->mailArchiveRepository = $mailArchiveRepository;
        $this->customerRepository = $customerRepository;
    }

    public function send(Email $email, ?Envelope $envelope = null): void
    {
        // let first send the mail itself, to see if it was really sent or entered error state
        $this->mailSender->send($email, $envelope);

        $this->saveMail($email);
    }

    public function getDecorated(): AbstractMailSender
    {
        return $this->mailSender;
    }

    private function saveMail(Email $message): void
    {
        $id = Uuid::randomHex();

        $emlPath = $this->emlFileManager->writeFile($id, $message->toString());

        $this->mailArchiveRepository->create([
            [
                'id' => $id,
                'sender' => [$message->getFrom()[0]->getAddress() => $message->getFrom()[0]->getName()],
                'receiver' => $this->convertAddress($message->getTo()),
                'subject' => $message->getSubject(),
                'plainText' => nl2br((string) $message->getTextBody()),
                'htmlText' => $message->getHtmlBody(),
                'emlPath' => $emlPath,
                'salesChannelId' => $this->getCurrentSalesChannelId(),
                'customerId' => $this->getCustomerIdByMail(array_keys($message->getTo())),
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

    private function getCustomerIdByMail(array $mails): ?string
    {
        $criteria = new Criteria();

        $criteria->addFilter(new EqualsAnyFilter('email', $mails));

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
