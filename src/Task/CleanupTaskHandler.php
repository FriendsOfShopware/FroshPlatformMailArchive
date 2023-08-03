<?php declare(strict_types=1);

namespace Frosh\MailArchive\Task;

use Doctrine\DBAL\Connection;
use Frosh\MailArchive\Content\MailArchive\MailArchiveDefinition;
use Frosh\MailArchive\Services\EmlFileManager;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(handles: CleanupTask::class)]
class CleanupTaskHandler extends ScheduledTaskHandler
{
    public function __construct(
        EntityRepository $scheduledTaskRepository,
        private readonly SystemConfigService $configService,
        private readonly Connection $connection,
        private readonly EmlFileManager $emlFileManager
    ) {
        parent::__construct($scheduledTaskRepository);
    }

    public function run(): void
    {
        $days = $this->configService->getInt('FroshPlatformMailArchive.config.deleteMessageAfterDays');

        if ($days === 0) {
            return;
        }

        $time = new \DateTime();
        $time->modify(sprintf('-%s days', $days));

        $query = $this->connection->createQueryBuilder();

        $query->select('id', 'eml_path');
        $query->from(MailArchiveDefinition::ENTITY_NAME);
        $query->where(
            $query->expr()->lte(
                'created_at',
                $query->createNamedParameter($time->format(Defaults::STORAGE_DATE_TIME_FORMAT))
            )
        );

        $result = $query->executeQuery()->fetchAllAssociative();

        if (\count($result) === 0) {
            return;
        }

        foreach ($result as $item) {
            $this->emlFileManager->deleteEmlFile($item['eml_path']);
        }

        $deleteQuery = $this->connection->createQueryBuilder();
        $deleteQuery->delete(MailArchiveDefinition::ENTITY_NAME);
        $deleteQuery->where(
            $query->expr()->in(
                'LOWER(HEX(id))',
                \array_map(fn ($item) => '"' . Uuid::fromBytesToHex($item['id']) . '"', $result)
            )
        );

        $deleteQuery->executeQuery();
    }
}
