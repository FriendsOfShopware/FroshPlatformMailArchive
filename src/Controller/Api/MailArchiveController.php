<?php

declare(strict_types=1);

namespace Frosh\MailArchive\Controller\Api;

use Frosh\MailArchive\Content\MailArchive\MailArchiveAttachmentEntity;
use Frosh\MailArchive\Content\MailArchive\MailArchiveEntity;
use Frosh\MailArchive\Content\MailArchive\MailArchiveException;
use Frosh\MailArchive\Services\EmlFileManager;
use Frosh\MailArchive\Services\MailSender;
use Shopware\Core\Content\Mail\Service\AbstractMailSender;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\PlatformRequest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;
use ZBateson\MailMimeParser\Header\AddressHeader;
use ZBateson\MailMimeParser\Header\DateHeader;
use ZBateson\MailMimeParser\Header\IHeader;
use ZBateson\MailMimeParser\Header\Part\AddressPart;

#[Route(defaults: ['_routeScope' => ['api']])]
class MailArchiveController extends AbstractController
{
    /**
     * @param EntityRepository<EntityCollection<MailArchiveEntity>> $froshMailArchiveRepository
     * @param EntityRepository<EntityCollection<MailArchiveAttachmentEntity>> $froshMailArchiveAttachmentRepository
     */
    public function __construct(
        private readonly EntityRepository   $froshMailArchiveRepository,
        private readonly EntityRepository   $froshMailArchiveAttachmentRepository,
        #[Autowire(service: MailSender::class)]
        private readonly AbstractMailSender $mailSender,
        private readonly RequestStack       $requestStack,
        private readonly EmlFileManager     $emlFileManager,
    ) {}

    #[Route(path: '/api/_action/frosh-mail-archive/fetch-eml-headers', name: 'api.action.frosh-mail-archive.fetch-eml-headers')]
    public function fetchEmlHeaders(Request $request, Context $context): JsonResponse
    {
        $mailId = $request->request->get('mailId');

        if (!\is_string($mailId)) {
            throw MailArchiveException::parameterMissing('mailId');
        }

        $mailArchive = $this->froshMailArchiveRepository->search(new Criteria([$mailId]), $context)->first();
        if (!$mailArchive instanceof MailArchiveEntity) {
            throw MailArchiveException::notFound();
        }

        $mainRequest = $this->requestStack->getMainRequest();
        if (!$mainRequest instanceof Request) {
            throw new \RuntimeException('Cannot get mainRequest');
        }

        $mainRequest->attributes->set(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_ID, $mailArchive->getSalesChannelId());

        $emlPath = $mailArchive->getEmlPath();
        if ($emlPath === null) {
            return new JsonResponse([
                'headers' => [],
            ]);
        }

        $emlMessage = $this->emlFileManager->getEmlAsMessage($emlPath);
        $headers = $emlMessage->getAllHeaders() ?? [];
        foreach ($emlMessage->getAllHeaders() as $header) {
            $headers[$header->getName()] = $header->getValue();
        }

        return new JsonResponse([
            'headers' => $headers,
        ]);
    }

    #[Route(path: '/api/_action/frosh-mail-archive/resend-mail', name: 'api.action.frosh-mail-archive.resend-mail')]
    public function resend(Request $request, Context $context): JsonResponse
    {
        $mailId = $request->request->get('mailId');

        if (!\is_string($mailId)) {
            throw MailArchiveException::parameterMissing('mailId');
        }

        $mailArchive = $this->froshMailArchiveRepository->search(new Criteria([$mailId]), $context)->first();
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
        $isEml = !empty($emlPath) && \is_string($emlPath);

        if ($isEml) {
            $this->enrichFromEml($emlPath, $email);
        } else {
            $this->enrichFromDatabase($mailArchive, $email);
        }

        $this->mailSender->send($email);

        $this->froshMailArchiveRepository->update([[
            'id' => $mailId,
            'transportState' => MailSender::TRANSPORT_STATE_RESENT,
        ]], $context);

        return new JsonResponse([
            'success' => true,
        ]);
    }

    #[Route(path: '/api/_action/frosh-mail-archive/content')]
    public function download(Request $request, Context $context): JsonResponse
    {
        $mailId = $request->request->get('mailId');

        if (!\is_string($mailId)) {
            throw MailArchiveException::parameterMissing('mailId');
        }

        $mailArchive = $this->froshMailArchiveRepository->search(new Criteria([$mailId]), $context)->first();
        if (!$mailArchive instanceof MailArchiveEntity) {
            throw MailArchiveException::notFound();
        }

        $content = $this->getContent($mailArchive->getEmlPath());

        if (empty($content)) {
            throw new \RuntimeException('Cannot read eml file or file is empty');
        }

        $fileNameParts = [];

        if ($mailArchive->getCreatedAt() !== null) {
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

    #[Route(path: '/api/_action/frosh-mail-archive/attachment', name: 'api.action.frosh-mail-archive.attachment')]
    public function attachment(Request $request, Context $context): JsonResponse
    {
        $attachmentId = $request->request->getString('attachmentId');
        if (!Uuid::isValid($attachmentId)) {
            throw MailArchiveException::parameterInvalidUuid('attachmentId');
        }

        $criteria = new Criteria([$attachmentId]);
        $criteria->addAssociation('mailArchive');

        $attachment = $this->froshMailArchiveAttachmentRepository->search($criteria, $context)->first();
        if (!$attachment instanceof MailArchiveAttachmentEntity) {
            throw MailArchiveException::notFound();
        }

        $mailArchive = $attachment->getMailArchive();
        if (!$mailArchive instanceof MailArchiveEntity) {
            throw MailArchiveException::notFound();
        }

        $emlPath = $mailArchive->getEmlPath();
        $isEml = !empty($emlPath) && \is_string($emlPath);

        if (!$isEml) {
            throw new \RuntimeException('Cannot read eml file or file is empty');
        }

        $message = $this->emlFileManager->getEmlAsMessage($emlPath);

        if (empty($message)) {
            throw new \RuntimeException('Cannot read eml file or file is empty');
        }

        $content = null;

        foreach ($message->getAllAttachmentParts() as $part) {
            if ($part->getFilename() === $attachment->getFileName()) {
                $content = $part->getContent();

                break;
            }
        }

        if (empty($content)) {
            throw new \RuntimeException('Cannot find attachment in eml file');
        }

        $fileNameParts = [];

        if ($mailArchive->getCreatedAt() !== null) {
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
            $headerValue = $this->getHeaderValue($header);

            // skip multipart/ headers due to multiple content types breaking the resent email
            if ($header->getName() === 'Content-Type' && \in_array($headerValue, ['multipart/alternative', 'multipart/mixed'], true)) {
                continue;
            }

            if ($header->getName() === 'Return-Path') {
                $headerValue = $this->determineReturnPath($headerValue);

                if ($headerValue === null) {
                    continue;
                }
            }

            $email->getHeaders()->addHeader($header->getName(), $headerValue);
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
     * @return string|array<string|Address>|\DateTimeImmutable|null
     */
    private function getHeaderValue(IHeader $header): string|array|null|\DateTimeImmutable
    {
        if ($header instanceof AddressHeader) {
            /** @var AddressPart[] $addressParts */
            $addressParts = $header->getParts();

            return \array_map(function (AddressPart $part) use ($header) {
                if ($header->getName() === 'Return-Path') {
                    return $part->getEmail();
                }

                return new Address($part->getEmail(), $part->getName());
            }, $addressParts);
        }

        if ($header instanceof DateHeader) {
            return $header->getDateTimeImmutable();
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
        if (empty($emlPath) || !\is_string($emlPath)) {
            return false;
        }

        return $this->emlFileManager->getEmlFileAsString($emlPath);
    }

    /**
     * @param \DateTimeImmutable|array<string|Address>|string|null $headerValue
     */
    private function determineReturnPath(\DateTimeImmutable|array|string|null $headerValue): ?string
    {
        // Extract first item for return-path since Symfony/Mailer needs to be a string value here
        if (is_array($headerValue)) {
            $headerValue = array_pop($headerValue);
        }

        // extract mail from: <"mail@example.com" <mail@example.com>>
        // see https://github.com/symfony/symfony/pull/59796
        if ($headerValue instanceof Address) {
            return $headerValue->getEncodedAddress();
        }

        if (is_string($headerValue)) {
            $regex = '/[<"]([^<>"\s]+@[^<>"\s]+)[>"]/';
            preg_match($regex, $headerValue, $matches);
            if (isset($matches[1])) {
                $headerValue = $matches[1];
            }
        }

        if (is_string($headerValue)) {
            try {
                return (new Address($headerValue))->getEncodedAddress();
            } catch (\Throwable) {
                // we don't care about invalid addresses
            }
        }

        return null;
    }
}
