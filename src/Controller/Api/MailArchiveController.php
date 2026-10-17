<?php

declare(strict_types=1);

namespace Frosh\MailArchive\Controller\Api;

use Frosh\MailArchive\Acl\MailArchivePrivileges;
use Frosh\MailArchive\Content\MailArchive\MailArchiveAttachmentEntity;
use Frosh\MailArchive\Content\MailArchive\MailArchiveEntity;
use Frosh\MailArchive\Content\MailArchive\MailArchiveException;
use Frosh\MailArchive\Services\EmlFileManager;
use Frosh\MailArchive\Services\MailSender;
use Frosh\MailArchive\Services\ResendRecipientOverride;
use Shopware\Core\Content\Mail\Service\AbstractMailSender;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Routing\ApiRouteScope;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\PlatformRequest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Exception\ExceptionInterface;
use Symfony\Component\Routing\Attribute\Route;
use ZBateson\MailMimeParser\Header\AddressHeader;
use ZBateson\MailMimeParser\Header\IHeader;
use ZBateson\MailMimeParser\Header\Part\AddressPart;

#[Route(defaults: [PlatformRequest::ATTRIBUTE_ROUTE_SCOPE => [ApiRouteScope::ID]])]
class MailArchiveController extends AbstractController
{
    private const RESEND_HEADER_ALLOWLIST = [
        'from',
        'to',
        'cc',
        'reply-to',
        'sender',
        'subject',
    ];

    /**
     * @param EntityRepository<EntityCollection<MailArchiveEntity>> $froshMailArchiveRepository
     * @param EntityRepository<EntityCollection<MailArchiveAttachmentEntity>> $froshMailArchiveAttachmentRepository
     */
    public function __construct(
        private readonly EntityRepository $froshMailArchiveRepository,
        private readonly EntityRepository $froshMailArchiveAttachmentRepository,
        #[Autowire(service: MailSender::class)]
        private readonly AbstractMailSender $mailSender,
        private readonly RequestStack $requestStack,
        private readonly EmlFileManager $emlFileManager,
        private readonly ResendRecipientOverride $resendRecipientOverride,
    ) {
    }

    #[Route(
        path: '/api/_action/frosh-mail-archive/resend-mail',
        name: 'api.action.frosh-mail-archive.resend-mail',
        defaults: [PlatformRequest::ATTRIBUTE_ACL => [MailArchivePrivileges::READ, MailArchivePrivileges::RESEND]],
        methods: [Request::METHOD_POST],
    )]
    public function resend(Request $request, Context $context): JsonResponse
    {
        $mailId = $request->request->get('mailId');

        if (!\is_string($mailId)) {
            throw MailArchiveException::parameterMissing('mailId');
        }

        $mailArchive = $this->froshMailArchiveRepository->search(new Criteria([$mailId]), $context)->getEntities()->first();
        if (!$mailArchive instanceof MailArchiveEntity) {
            throw MailArchiveException::notFound();
        }

        $mainRequest = $this->requestStack->getMainRequest();
        if (!$mainRequest instanceof Request) {
            throw new \RuntimeException('Cannot get mainRequest');
        }

        $mainRequest->attributes->set(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_ID, $mailArchive->getSalesChannelId());

        $email = new Email();
        $emlPath = $mailArchive->getEmlPath();
        $isEml = $emlPath !== '' && $emlPath !== '0' && \is_string($emlPath);

        if ($isEml) {
            $this->enrichFromEml($emlPath, $email);
        } else {
            $this->enrichFromDatabase($mailArchive, $email);
        }

        $receiver = $request->request->all()['receiver'] ?? null;

        if ($receiver !== null) {
            $this->resendRecipientOverride->replace($email, $this->createReceiverAddresses($receiver));
        }

        $this->mailSender->send($email);

        $context->scope(Context::SYSTEM_SCOPE, function (Context $systemContext) use ($mailId): void {
            $this->froshMailArchiveRepository->update([[
                'id' => $mailId,
                'transportState' => MailSender::TRANSPORT_STATE_RESENT,
            ]], $systemContext);
        });

        return new JsonResponse([
            'success' => true,
        ]);
    }

    #[Route(
        path: '/api/_action/frosh-mail-archive/content',
        name: 'api.action.frosh-mail-archive.content',
        defaults: [PlatformRequest::ATTRIBUTE_ACL => [MailArchivePrivileges::READ]],
        methods: [Request::METHOD_POST],
    )]
    public function download(Request $request, Context $context): JsonResponse
    {
        $mailId = $request->request->get('mailId');

        if (!\is_string($mailId)) {
            throw MailArchiveException::parameterMissing('mailId');
        }

        $mailArchive = $this->froshMailArchiveRepository->search(new Criteria([$mailId]), $context)->getEntities()->first();
        if (!$mailArchive instanceof MailArchiveEntity) {
            throw MailArchiveException::notFound();
        }

        $content = $this->getContent($mailArchive->getEmlPath());

        if ($content === false || $content === '') {
            throw new \RuntimeException('Cannot read eml file or file is empty');
        }

        $fileNameParts = [];

        if ($mailArchive->getCreatedAt() instanceof \DateTimeInterface) {
            $fileNameParts[] = $mailArchive->getCreatedAt()->format('Y-m-d_H-i-s');
        }

        $fileNameParts[] = $mailArchive->getSubject();

        $fileName = $this->getFileName($fileNameParts) . '.eml';

        return new JsonResponse([
            'success' => true,
            'content' => $content,
            'fileName' => $fileName,
        ]);
    }

    #[Route(
        path: '/api/_action/frosh-mail-archive/attachment',
        name: 'api.action.frosh-mail-archive.attachment',
        defaults: [PlatformRequest::ATTRIBUTE_ACL => [MailArchivePrivileges::READ, MailArchivePrivileges::ATTACHMENT_READ]],
        methods: [Request::METHOD_POST],
    )]
    public function attachment(Request $request, Context $context): JsonResponse
    {
        $attachmentId = $request->request->getString('attachmentId');
        if (!Uuid::isValid($attachmentId)) {
            throw MailArchiveException::parameterInvalidUuid('attachmentId');
        }

        $criteria = new Criteria([$attachmentId]);
        $criteria->addAssociation('mailArchive');

        $attachment = $this->froshMailArchiveAttachmentRepository->search($criteria, $context)->getEntities()->first();
        if (!$attachment instanceof MailArchiveAttachmentEntity) {
            throw MailArchiveException::notFound();
        }

        $mailArchive = $attachment->getMailArchive();
        if (!$mailArchive instanceof MailArchiveEntity) {
            throw MailArchiveException::notFound();
        }

        $emlPath = $mailArchive->getEmlPath();
        $isEml = $emlPath !== '' && $emlPath !== '0' && \is_string($emlPath);

        if (!$isEml) {
            throw new \RuntimeException('Cannot read eml file or file is empty');
        }

        $message = $this->emlFileManager->getEmlAsMessage($emlPath);

        if ($message === false) {
            throw new \RuntimeException('Cannot read eml file or file is empty');
        }

        $content = null;

        foreach ($message->getAllAttachmentParts() as $part) {
            if ($part->getFilename() === $attachment->getFileName()) {
                $content = $part->getContent();

                break;
            }
        }

        if ($content === null || $content === '') {
            throw new \RuntimeException('Cannot find attachment in eml file');
        }

        $fileNameParts = [];

        if ($mailArchive->getCreatedAt() instanceof \DateTimeInterface) {
            $fileNameParts[] = $mailArchive->getCreatedAt()->format('Y-m-d_H-i-s');
        }

        $fileNameParts[] = $mailArchive->getSubject();
        $fileNameParts[] = $attachment->getFileName();

        $fileName = $this->getFileName($fileNameParts);

        return new JsonResponse([
            'success' => true,
            'content' => \base64_encode($content),
            'contentType' => $attachment->getContentType(),
            'fileName' => $fileName,
        ]);
    }

    private function enrichFromEml(string $emlPath, Email $email): void
    {
        $message = $this->emlFileManager->getEmlAsMessage($emlPath);

        if ($message === false) {
            throw new \RuntimeException('Cannot read eml file');
        }

        $email->html($message->getHtmlContent());
        $email->text($message->getTextContent());

        foreach ($message->getAllHeaders() as $header) {
            if (!\in_array(\strtolower($header->getName()), self::RESEND_HEADER_ALLOWLIST, true)) {
                continue;
            }

            $email->getHeaders()->addHeader($header->getName(), $this->getHeaderValue($header));
        }

        foreach ($message->getAllAttachmentParts() as $attachment) {
            if ($attachment->getContent() === null) {
                continue;
            }

            $email->attach($attachment->getContent(), $attachment->getFilename(), $attachment->getContentType());
        }
    }

    private function enrichFromDatabase(MailArchiveEntity $mailArchive, Email $email): void
    {
        foreach ($mailArchive->getReceiver() as $mail => $name) {
            $email->addTo(new Address($mail, $name));
        }

        foreach ($mailArchive->getSender() as $mail => $name) {
            $email->from(new Address($mail, $name));
        }

        $email->subject($mailArchive->getSubject());

        $email->html($mailArchive->getHtmlText());
        $email->text($mailArchive->getPlainText());
    }

    /**
     * @return non-empty-list<Address>
     */
    private function createReceiverAddresses(mixed $receiver): array
    {
        if (!\is_array($receiver) || $receiver === []) {
            throw MailArchiveException::parameterInvalidReceiver();
        }

        $addresses = [];

        foreach ($receiver as $entry) {
            if (!\is_string($entry) || \trim($entry) === '') {
                throw MailArchiveException::parameterInvalidReceiver();
            }

            try {
                $addresses[] = Address::create(\trim($entry));
            } catch (ExceptionInterface) {
                throw MailArchiveException::parameterInvalidReceiver();
            }
        }

        return $addresses;
    }

    /**
     * @return string|array<string|Address>|null
     */
    private function getHeaderValue(IHeader $header): string|array|null
    {
        if ($header instanceof AddressHeader) {
            /** @var AddressPart[] $addressParts */
            $addressParts = $header->getParts();

            return \array_map(
                static fn (AddressPart $part) => new Address($part->getEmail(), $part->getName()),
                $addressParts,
            );
        }

        return $header->getValue();
    }

    /**
     * @param array<string> $fileNameParts
     */
    private function getFileName(array $fileNameParts): string
    {
        return (string) preg_replace(
            '/[\x00-\x1F\x7F-\xFF]/',
            '',
            \implode(' ', $fileNameParts),
        );
    }

    private function getContent(?string $emlPath): false|string
    {
        if ($emlPath === '' || $emlPath === '0' || !\is_string($emlPath)) {
            return false;
        }

        return $this->emlFileManager->getEmlFileAsString($emlPath);
    }
}
