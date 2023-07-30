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

        if (!empty($mailArchive->getEmlPath())
            && $this->privateFilesystem->has($mailArchive->getEmlPath())) {
            $this->loadFromEml($email, $mailArchive);
        }

        //TODO: implement check for old data
        /*foreach ($mailArchive->getReceiver() as $mail => $name) {
            $email->addTo(new Address($mail, $name));
        }

        foreach ($mailArchive->getSender() as $mail => $name) {
            $email->from(new Address($mail, $name));
        }

        $email->subject($mailArchive->getSubject());

        $email->html($mailArchive->getHtmlText());
        $email->text($mailArchive->getPlainText());
        */

        $this->mailSender->send($email);

        return new JsonResponse([
            'success' => true,
        ]);
    }

    private function loadFromEml(Email $email, MailArchiveEntity $mailArchive): void
    {
        $eml = $this->privateFilesystem->readStream($mailArchive->getEmlPath());
        $parser = new MailMimeParser();
        $message = $parser->parse($eml, false);

        $email->html($message->getHtmlContent());
        $email->text($message->getTextContent());

        foreach ($message->getAllHeaders() as $header) {
            $value = $header->getValue();

            if ($header instanceof AddressHeader) {
                /** @var AddressPart[] $addParts */
                $addParts = $header->getParts();

                $value = \array_map(function (AddressPart $part) {
                    return new Address($part->getEmail(), $part->getName());
                }, $addParts);
            }

            if ($header instanceof DateHeader) {
                $value = $header->getDateTimeImmutable();
            }

            $email->getHeaders()->addHeader($header->getName(), $value);
        }

        foreach ($message->getAllAttachmentParts() as $attachment) {
            $email->attach($attachment->getContent(), $attachment->getFilename(), $attachment->getContentType());
        }
    }
}
