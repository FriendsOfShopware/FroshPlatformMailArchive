<?php

namespace Frosh\MailArchive\Controller\Api;

use Shopware\Core\Content\MailTemplate\Service\MailSender;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
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

    public function __construct(EntityRepositoryInterface $mailArchiveRepository, MailSender $mailSender)
    {
        $this->mailArchiveRepository = $mailArchiveRepository;
        $this->mailSender = $mailSender;
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

        $mailArchive = $this->mailArchiveRepository->search(new Criteria([$mailId]), Context::createDefaultContext())->first();

        if (!$mailArchive) {
            throw new \RuntimeException('Cannot find mailArchive');
        }

        $message = new \Swift_Message();

        foreach ($mailArchive->offsetGet('receiver') as $mail => $name) {
            $message->addTo($mail, $name);
        }

        $message->addFrom($mailArchive->offsetGet('sender'));
        $message->setSubject($mailArchive->offsetGet('subject'));

        if (!empty($mailArchive->offsetGet('plainText'))) {
            $message->addPart($mailArchive->offsetGet('plainText'), 'text/plain');
        }

        if (!empty($mailArchive->offsetGet('htmlText'))) {
            $message->addPart($mailArchive->offsetGet('htmlText'), 'text/html');
        }

        $this->mailSender->send($message);

        return new JsonResponse([
            'success' => true
        ]);
    }
}
