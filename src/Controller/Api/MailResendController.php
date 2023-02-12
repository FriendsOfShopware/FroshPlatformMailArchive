<?php declare(strict_types=1);

namespace Frosh\MailArchive\Controller\Api;

use Frosh\MailArchive\Content\MailArchive\MailArchiveEntity;
use Frosh\MailArchive\Services\MailSender;
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

class MailResendController extends AbstractController
{
    private readonly EntityRepository $mailArchiveRepository;

    public function __construct(
        EntityRepository $mailArchiveRepository,
        private readonly MailSender $mailSender,
        private readonly RequestStack $requestStack
    ) {
        $this->mailArchiveRepository = $mailArchiveRepository;
    }

    #[Route(path: '/api/_action/frosh-mail-archive/resend-mail', defaults: ['_routeScope' => ['api']])]
    public function resend(Request $request): JsonResponse
    {
        $mailId = $request->request->get('mailId');

        if (!is_string($mailId)) {
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
            'success' => true,
        ]);
    }
}
