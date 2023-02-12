<?php declare(strict_types=1);

namespace Frosh\MailArchive\Task;

use Doctrine\DBAL\Connection;
use Frosh\MailArchive\Content\MailArchive\MailArchiveDefinition;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(handles: CleanupTask::class)]
class CleanupTaskHandler extends ScheduledTaskHandler
{
    public function __construct(
        EntityRepository $scheduledTaskRepository,
        private readonly SystemConfigService $configService,
        private readonly Connection $connection
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
        $query->delete(MailArchiveDefinition::ENTITY_NAME);
        $query->where(
            $query->expr()->lte(
                'created_at',
                $query->createNamedParameter($time->format(Defaults::STORAGE_DATE_TIME_FORMAT))
            )
        );

        $query->executeQuery();
    }
}
