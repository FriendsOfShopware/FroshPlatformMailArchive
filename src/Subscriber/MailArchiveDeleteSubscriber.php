<?php declare(strict_types=1);

namespace Frosh\MailArchive\Subscriber;

use Frosh\MailArchive\Content\MailArchive\MailArchiveDefinition;
use Frosh\MailArchive\Content\MailArchive\MailArchiveEntity;
use Frosh\MailArchive\Services\EmlFileManager;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Event\BeforeDeleteEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MailArchiveDeleteSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly EntityRepository $froshMailArchiveRepository,
        private readonly EmlFileManager $emlFileManager
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            BeforeDeleteEvent::class => 'beforeDelete',
        ];
    }

    public function beforeDelete(BeforeDeleteEvent $event): void
    {
        /** @var list<string> $affected */
        $ids = array_values($event->getIds(MailArchiveDefinition::ENTITY_NAME));
        if (empty($ids)) {
            return;
        }

        $criteria = new Criteria($ids);
        $mails = $this->froshMailArchiveRepository->search($criteria, $event->getContext())->getEntities();

        /** @var MailArchiveEntity $mail */
        foreach ($mails as $mail) {
            $emlPath = $mail->getEmlPath();
            if (empty($emlPath)) {
                continue;
            }

            $this->emlFileManager->deleteEmlFile($emlPath);
        }
    }
}
