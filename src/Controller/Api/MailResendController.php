<?php

namespace Frosh\MailArchive\Controller\Api;

use Frosh\MailArchive\Content\MailArchive\MailArchiveEntity;
use Shopware\Core\Content\MailTemplate\Service\MailSender;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\PlatformRequest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Annotation\Route;

class MailResendController extends AbstractController
{
    /**
     * @var EntityRepositoryInterface
     */
    private $mailArchiveRepository;

    /**
     * @var MailSender
     */
    private $mailSender;

    /**
     * @var RequestStack
     */
    private $requestStack;

    public function __construct(EntityRepositoryInterface $mailArchiveRepository, MailSender $mailSender, RequestStack $requestStack)
    {
        $this->mailArchiveRepository = $mailArchiveRepository;
        $this->mailSender = $mailSender;
        $this->requestStack = $requestStack;
    }

    /**
     * @RouteScope(scopes={"api"})
     * @Route(path="/api/v{version}/_action/frosh-mail-archive/resend-mail")
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

        $this->requestStack->getMasterRequest()->attributes->set(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_ID, $mailArchive->getSalesChannelId());

        $message = new \Swift_Message();

        foreach ($mailArchive->getReceiver() as $mail => $name) {
            $message->addTo($mail, $name);
        }

        $message->addFrom($mailArchive->getSender());
        $message->setSubject($mailArchive->getSubject());

        if (!empty($mailArchive->getPlainText())) {
            $message->addPart($mailArchive->getPlainText(), 'text/plain');
        }

        if (!empty($mailArchive->getHtmlText())) {
            $message->addPart($mailArchive->getHtmlText(), 'text/html');
        }

        $this->mailSender->send($message);

        return new JsonResponse([
            'success' => true
        ]);
    }
}
