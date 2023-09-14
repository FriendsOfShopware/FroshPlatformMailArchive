<?php declare(strict_types=1);

namespace Frosh\MailArchive\Controller\Api;

use Frosh\MailArchive\Content\MailArchive\MailArchiveAttachmentEntity;
use Frosh\MailArchive\Content\MailArchive\MailArchiveEntity;
use Frosh\MailArchive\Content\MailArchive\MailArchiveException;
use Frosh\MailArchive\Services\EmlFileManager;
use Shopware\Core\Content\Mail\Service\AbstractMailSender;
use Shopware\Core\Content\Mail\Service\MailSender;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
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
    public function __construct(
        private readonly EntityRepository $froshMailArchiveRepository,
        private readonly EntityRepository $froshMailArchiveAttachmentRepository,
        #[Autowire(service: MailSender::class)]
        private readonly AbstractMailSender $mailSender,
        private readonly RequestStack $requestStack,
        private readonly EmlFileManager $emlFileManager
    ) {
    }

    #[Route(path: '/api/_action/frosh-mail-archive/resend-mail', name: 'api.action.frosh-mail-archive.resend-mail')]
    public function resend(Request $request): JsonResponse
    {
        $mailId = $request->request->get('mailId');

        if (!\is_string($mailId)) {
            throw MailArchiveException::parameterMissing('mailId');
        }

        $mailArchive = $this->froshMailArchiveRepository->search(new Criteria([$mailId]), Context::createDefaultContext())->first();
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

        return new JsonResponse([
            'success' => true,
        ]);
    }

    #[Route(path: '/api/_action/frosh-mail-archive/content')]
    public function download(Request $request): JsonResponse
    {
        $mailId = $request->request->get('mailId');

        if (!\is_string($mailId)) {
            throw MailArchiveException::parameterMissing('mailId');
        }

        $mailArchive = $this->froshMailArchiveRepository->search(new Criteria([$mailId]), Context::createDefaultContext())->first();
        if (!$mailArchive instanceof MailArchiveEntity) {
            throw MailArchiveException::notFound();
        }

        // for backward compatibility
        $content = $mailArchive->getEml();

        $emlPath = $mailArchive->getEmlPath();
        $isEml = !empty($emlPath) && \is_string($emlPath);

        if ($isEml) {
            $content = $this->emlFileManager->getEmlFileAsString($emlPath);
        }

        if (empty($content)) {
            throw new \RuntimeException('Cannot read eml file or file is empty');
        }

        $fileName = $mailArchive->getCreatedAt()->format('Y-m-d_H-i-s') . ' ' . $mailArchive->getSubject() . '.eml';

        return new JsonResponse([
            'success' => true,
            'content' => $content,
            'fileName' => preg_replace('/[\x00-\x1F\x7F-\xFF]/', '', $fileName),
        ]);
    }

    #[Route(path: '/api/_action/frosh-mail-archive/attachment', name: 'api.action.frosh-mail-archive.attachment')]
    public function attachment(Request $request): JsonResponse
    {
        $attachmentId = $request->request->get('attachmentId');
        if (!\is_string($attachmentId)) {
            throw MailArchiveException::parameterMissing('attachmentId');
        }

        $criteria = new Criteria([$attachmentId]);
        $criteria->addAssociation('mailArchive');

        $attachment = $this->froshMailArchiveAttachmentRepository->search($criteria, Context::createDefaultContext())->first();
        if (!$attachment instanceof MailArchiveAttachmentEntity) {
            throw MailArchiveException::notFound();
        }

        $mailArchive = $attachment->getMailArchive();

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

        $fileName = $mailArchive->getCreatedAt()->format('Y-m-d_H-i-s') . ' ' . $mailArchive->getSubject() . ' ' . $attachment->getFileName();

        return new JsonResponse([
            'success' => true,
            'content' => \base64_encode($content),
            'contentType' => $attachment->getContentType(),
            'fileName' => preg_replace('/[\x00-\x1F\x7F-\xFF]/', '', $fileName),
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

            $email->getHeaders()->addHeader($header->getName(), $headerValue);
        }

        foreach ($message->getAllAttachmentParts() as $attachment) {
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

        $this->emlFileManager->migrateMailToFilesystem([$mailArchive->getId()]);
    }

    private function getHeaderValue(IHeader $header): string|array|null|\DateTimeImmutable
    {
        if ($header instanceof AddressHeader) {
            /** @var AddressPart[] $addressParts */
            $addressParts = $header->getParts();

            return \array_map(function (AddressPart $part) {
                return new Address($part->getEmail(), $part->getName());
            }, $addressParts);
        }

        if ($header instanceof DateHeader) {
            return $header->getDateTimeImmutable();
        }

        return $header->getValue();
    }
}
