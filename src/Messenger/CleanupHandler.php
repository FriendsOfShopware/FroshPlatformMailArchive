<?php

declare(strict_types=1);

namespace Frosh\MailArchive\Messenger;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Frosh\MailArchive\Content\MailArchive\MailArchiveDefinition;
use Frosh\MailArchive\Services\EmlFileManager;
use Shopware\Core\Defaults;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class CleanupHandler
{
    public function __construct(
        private readonly SystemConfigService $configService,
        private readonly Connection $connection,
        private readonly EmlFileManager $emlFileManager,
    ) {}

    public function __invoke(CleanupMessage $message): void
    {
        $days = $this->configService->getInt('FroshPlatformMailArchive.config.deleteMessageAfterDays');

        if ($days === 0) {
            return;
        }

        $time = new \DateTime();
        $time->modify(\sprintf('-%s days', $days));

        $query = $this->connection->createQueryBuilder();

        $query->select('id', 'eml_path');
        $query->from(MailArchiveDefinition::ENTITY_NAME);
        $query->where(
            $query->expr()->lte(
                'created_at',
                $query->createNamedParameter($time->format(Defaults::STORAGE_DATE_TIME_FORMAT)),
            ),
        );

        $result = $query->executeQuery()->fetchAllAssociative();

        if (\count($result) === 0) {
            return;
        }

        foreach ($result as $item) {
            if (empty($item['eml_path']) || !\is_string($item['eml_path'])) {
                continue;
            }

            $this->emlFileManager->deleteEmlFile($item['eml_path']);
        }

        $deleteQuery = $this->connection->createQueryBuilder();
        $deleteQuery->delete(MailArchiveDefinition::ENTITY_NAME);
        $deleteQuery->where('id IN (:ids)');
        $deleteQuery->setParameter('ids', \array_column($result, 'id'), ArrayParameterType::STRING);

        $deleteQuery->executeQuery();
    }
}
