<?php

namespace Frosh\MailArchive\Task;

use DateInterval;
use DateTime;
use Doctrine\DBAL\Connection;
use Frosh\MailArchive\Content\MailArchive\MailArchiveDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use function Doctrine\DBAL\Query\QueryBuilder;

class CleanupTaskHandler extends ScheduledTaskHandler
{
    private SystemConfigService $configService;
    private Connection $connection;

    public function __construct(
        EntityRepositoryInterface $scheduledTaskRepository,
        SystemConfigService $configService,
        Connection $connection
    ) {
        parent::__construct($scheduledTaskRepository);
        $this->configService = $configService;
        $this->connection = $connection;
    }

    public static function getHandledMessages(): iterable
    {
        return [
            CleanupTask::class
        ];
    }

    public function run(): void
    {
        $days = $this->configService->getInt('FroshPlatformMailArchive.config.deleteMessageAfterDays');

        if ($days === 0) {
            return;
        }

        $time = (new DateTime())->sub(DateInterval::createFromDateString(sprintf('%s days', $days)));

        $query = $this->connection->createQueryBuilder();
        $query->delete(MailArchiveDefinition::ENTITY_NAME);
        $query->where($query->expr()->lte('created_at', $query->createNamedParameter($time->format('Y-m-d H:i:s'))));

        $query->execute();
    }
}
