<?php declare(strict_types=1);

namespace Frosh\MailArchive\MessageQueue;

use Frosh\MailArchive\Content\MailArchive\MailArchiveEntity;
use Frosh\MailArchive\Services\EmlFileManager;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(priority: 100)]
class MigrateMailHandler
{
    public function __construct(
        private readonly EntityRepository $froshMailArchiveRepository,
        private readonly EmlFileManager $emlFileManager
    ) {
    }

    public function __invoke(MigrateMailMessage $message): void
    {
        $mails = $this->froshMailArchiveRepository->search(new Criteria($message->ids), Context::createDefaultContext())->getEntities();

        $updates = [];

        /** @var MailArchiveEntity $mail */
        foreach ($mails as $mail) {
            $emlContent = $mail->getEml();

            if (empty($emlContent)) {
                continue;
            }

            $updates[] = [
                'id' => $mail->getId(),
                'emlPath' => $this->emlFileManager->writeFile($mail->getId(), $emlContent),
                'eml' => null,
            ];
        }

        $this->froshMailArchiveRepository->update($updates, Context::createDefaultContext());
    }
}
