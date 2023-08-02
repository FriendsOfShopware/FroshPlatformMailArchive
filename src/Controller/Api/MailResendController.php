<?php declare(strict_types=1);

namespace Frosh\MailArchive\Controller\Api;

use Frosh\MailArchive\Content\MailArchive\MailArchiveEntity;
use Frosh\MailArchive\Services\EmlFileManager;
use Frosh\MailArchive\Services\MailSender;
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

class MailResendController extends AbstractController
{
    public function __construct(
        private readonly EntityRepository $froshMailArchiveRepository,
        private readonly MailSender $mailSender,
        private readonly RequestStack $requestStack,
        private readonly EmlFileManager $emlFileManager
    ) {
    }

    #[Route(path: '/api/_action/frosh-mail-archive/resend-mail', defaults: ['_routeScope' => ['api']])]
    public function resend(Request $request): JsonResponse
    {
        $mailId = $request->request->get('mailId');

        if (!\is_string($mailId)) {
            throw new \RuntimeException('mailId not given');
        }

        $mailArchive = $this->froshMailArchiveRepository->search(new Criteria([$mailId]), Context::createDefaultContext())->first();
        if (!$mailArchive instanceof MailArchiveEntity) {
            throw new \RuntimeException('Cannot find mail in archive');
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

    #[Route(path: '/api/_action/frosh-mail-archive/download-mail', defaults: ['_routeScope' => ['api']])]
    public function download(Request $request): JsonResponse
    {
        $mailId = $request->request->get('mailId');

        if (!\is_string($mailId)) {
            throw new \RuntimeException('mailId not given');
        }

        $mailArchive = $this->froshMailArchiveRepository->search(new Criteria([$mailId]), Context::createDefaultContext())->first();
        if (!$mailArchive instanceof MailArchiveEntity) {
            throw new \RuntimeException('Cannot find mailArchive');
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

            // skip multipart/alternative header due to multiple content types breaking the resent email
            if ($header->getName() === 'Content-Type' && $headerValue === 'multipart/alternative') {
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
