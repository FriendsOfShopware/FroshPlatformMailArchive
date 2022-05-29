<?php

namespace Frosh\MailArchive\Controller\Api;

use Frosh\MailArchive\Content\MailArchive\MailArchiveEntity;
use Frosh\MailArchive\Services\MailSender;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\PlatformRequest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;

class MailResendController extends AbstractController
{
    private EntityRepositoryInterface $mailArchiveRepository;

    private MailSender $mailSender;

    private RequestStack $requestStack;

    public function __construct(EntityRepositoryInterface $mailArchiveRepository, MailSender $mailSender, RequestStack $requestStack)
    {
        $this->mailArchiveRepository = $mailArchiveRepository;
        $this->mailSender = $mailSender;
        $this->requestStack = $requestStack;
    }

    /**
     * @RouteScope(scopes={"api"})
     * @Route(path="/api/_action/frosh-mail-archive/resend-mail")
     */
    public function resend(Request $request)
    {
        $mailId = $request->request->get('mailId');

        if (!$mailId) {
            throw new \RuntimeException('mailId not given');
        }

        /** @var MailArchiveEntity|null $mailArchive */
        $mailArchive = $this->mailArchiveRepository->search(new Criteria([$mailId]), Context::createDefaultContext())->first();

        if (!$mailArchive) {
            throw new \RuntimeException('Cannot find mailArchive');
        }

        $this->requestStack->getMainRequest()->attributes->set(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_ID, $mailArchive->getSalesChannelId());

        $message = new Email();

        foreach ($mailArchive->getReceiver() as $mail => $name) {
            $message->addTo(new Address($mail, $name));
        }

        foreach ($mailArchive->getSender() as $mail => $name) {
            $message->from(new Address($mail, $name));
        }

        $message->subject($mailArchive->getSubject());

        $message->html($mailArchive->getHtmlText());
        $message->text($mailArchive->getPlainText());

        $this->mailSender->send($message);

        return new JsonResponse([
            'success' => true
        ]);
    }
}
