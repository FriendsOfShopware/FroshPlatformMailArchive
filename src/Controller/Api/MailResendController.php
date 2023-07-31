<?php declare(strict_types=1);

namespace Frosh\MailArchive\Controller\Api;

use Frosh\MailArchive\Content\MailArchive\MailArchiveEntity;
use Frosh\MailArchive\Services\MailSender;
use League\Flysystem\FilesystemOperator;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\PlatformRequest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
use ZBateson\MailMimeParser\MailMimeParser;

class MailResendController extends AbstractController
{
    private readonly EntityRepository $mailArchiveRepository;

    public function __construct(
        EntityRepository $mailArchiveRepository,
        private readonly MailSender $mailSender,
        private readonly RequestStack $requestStack,
        private readonly FilesystemOperator $privateFilesystem
    ) {
        $this->mailArchiveRepository = $mailArchiveRepository;
    }

    #[Route(path: '/api/_action/frosh-mail-archive/resend-mail', defaults: ['_routeScope' => ['api']])]
    public function resend(Request $request): JsonResponse
    {
        $mailId = $request->request->get('mailId');

        if (!\is_string($mailId)) {
            throw new \RuntimeException('mailId not given');
        }

        $mailArchive = $this->mailArchiveRepository->search(new Criteria([$mailId]), Context::createDefaultContext())->first();
        if (!$mailArchive instanceof MailArchiveEntity) {
            throw new \RuntimeException('Cannot find mailArchive');
        }

        $mainRequest = $this->requestStack->getMainRequest();
        if (!$mainRequest instanceof Request) {
            throw new \RuntimeException('Cannot get mainRequest');
        }

        $mainRequest->attributes->set(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_ID, $mailArchive->getSalesChannelId());

        $email = new Email();
        $emlPath = $mailArchive->getEmlPath();
        $isEml = !empty($emlPath) && \is_string($emlPath) && $this->privateFilesystem->has($emlPath);

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

        $mailArchive = $this->mailArchiveRepository->search(new Criteria([$mailId]), Context::createDefaultContext())->first();
        if (!$mailArchive instanceof MailArchiveEntity) {
            throw new \RuntimeException('Cannot find mailArchive');
        }

        $emlPath = $mailArchive->getEmlPath();
        $isEml = !empty($emlPath) && \is_string($emlPath) && $this->privateFilesystem->has($emlPath);

        $content = $mailArchive->getEml();

        if ($isEml) {
            $content = $this->privateFilesystem->read($emlPath);
        }

        if (empty($content)) {
            throw new \RuntimeException('Eml content is empty');
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
        $eml = $this->privateFilesystem->readStream($emlPath);
        $parser = new MailMimeParser();
        $message = $parser->parse($eml, false);

        $email->html($message->getHtmlContent());
        $email->text($message->getTextContent());

        foreach ($message->getAllHeaders() as $header) {
            $email->getHeaders()->addHeader($header->getName(), $this->getHeaderValue($header));
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
