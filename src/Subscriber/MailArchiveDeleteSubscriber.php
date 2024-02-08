<?php

declare(strict_types=1);

namespace Frosh\MailArchive\Subscriber;

use Frosh\MailArchive\Content\MailArchive\MailArchiveDefinition;
use Frosh\MailArchive\Content\MailArchive\MailArchiveEntity;
use Frosh\MailArchive\Services\EmlFileManager;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityDeleteEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MailArchiveDeleteSubscriber implements EventSubscriberInterface
{
    /**
     * @param EntityRepository<EntityCollection<MailArchiveEntity>> $froshMailArchiveRepository
     */
    public function __construct(
        private readonly EntityRepository $froshMailArchiveRepository,
        private readonly EmlFileManager   $emlFileManager
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            EntityDeleteEvent::class => 'beforeDelete',
        ];
    }

    public function beforeDelete(EntityDeleteEvent $event): void
    {
        /** @var array<string> $ids */
        $ids = array_values($event->getIds(MailArchiveDefinition::ENTITY_NAME));
        if (empty($ids)) {
            return;
        }

        $criteria = new Criteria($ids);
        $criteria->addFields(['emlPath']);
        $mails = $this->froshMailArchiveRepository->search($criteria, $event->getContext())->getEntities();

        foreach ($mails as $mail) {
            $emlPath = $mail->get('emlPath');
            if (empty($emlPath) || !\is_string($emlPath)) {
                continue;
            }

            $event->addSuccess(function () use ($emlPath) {
                $this->emlFileManager->deleteEmlFile($emlPath);
            });
        }
    }
}
