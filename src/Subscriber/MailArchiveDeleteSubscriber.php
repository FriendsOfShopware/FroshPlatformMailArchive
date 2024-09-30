<?php

declare(strict_types=1);

namespace Frosh\MailArchive\Subscriber;

use Frosh\MailArchive\Content\MailArchive\MailArchiveDefinition;
use Frosh\MailArchive\Services\EmlFileManager;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Event\BeforeDeleteEvent;
use Shopware\Core\Framework\DataAbstractionLayer\PartialEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NorFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\OrFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
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
        /** @var array<string> $ids */
        $ids = array_values($event->getIds(MailArchiveDefinition::ENTITY_NAME));
        if (empty($ids)) {
            return;
        }

        $criteria = new Criteria($ids);
        $criteria->addFields(['emlPath', 'parentId']);
        $mails = $this->froshMailArchiveRepository->search($criteria, $event->getContext())->getEntities();

        /** @var PartialEntity $mail */
        foreach ($mails as $mail) {
            $emlPath = $mail->get('emlPath');
            if (empty($emlPath) || !\is_string($emlPath)) {
                continue;
            }

            $this->emlFileManager->deleteEmlFile($emlPath);
        }

        $this->updateHistoryLastMail($ids, $mails, $event->getContext());
    }

    /**
     * @param array<string> $deleteIds
     * @param EntityCollection<PartialEntity> $mails
     */
    public function updateHistoryLastMail(array $deleteIds, EntityCollection $mails, Context $context): void
    {
        // determine last email in email history
        $parentIds = [];
        /** @var PartialEntity $mail */
        foreach ($mails->getElements() as $mail) {
            /** @var string|null $parentId */
            $parentId = $mail->get('parentId');
            if ($parentId !== null) {
                $parentIds[$parentId] = $parentId;
            }
        }

        /** @var array<string> $ids */
        $ids = [];
        foreach ($parentIds as $parentId) {
            $criteria = (new Criteria())
                ->setLimit(1)
                ->addSorting(new FieldSorting('createdAt', FieldSorting::DESCENDING))
                ->addFilter(
                    new OrFilter([
                        new EqualsFilter('id', $parentId),
                        new EqualsFilter('parentId', $parentId),
                    ]),
                    new NorFilter([
                        new EqualsAnyFilter('id', $deleteIds),
                    ])
                );

            /** @var array<string> $tmpIds */
            $tmpIds = $this->froshMailArchiveRepository->searchIds($criteria, $context)->getIds();

            $ids = [...$ids, ...$tmpIds];
        }
        if (empty($ids)) {
            return;
        }

        $payload = array_map(fn ($id) => [
            'id' => $id,
            'historyLastMail' => true,
        ], $ids);

        $this->froshMailArchiveRepository->update($payload, $context);
    }
}
