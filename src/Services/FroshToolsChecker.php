<?php declare(strict_types=1);

namespace Frosh\MailArchive\Services;

use Frosh\MailArchive\Content\MailArchive\MailArchiveEntity;
use Frosh\Tools\Components\Health\Checker\CheckerInterface;
use Frosh\Tools\Components\Health\HealthCollection;
use Frosh\Tools\Components\Health\SettingsResult;
use Shopware\Core\Framework\Api\Context\SystemSource;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

if (\interface_exists(CheckerInterface::class)
    && \class_exists(HealthCollection::class)
    && \class_exists(SettingsResult::class)) {
    #[AutoconfigureTag('frosh_tools.health_checker')]
    class FroshToolsChecker implements CheckerInterface
    {
        /**
         * @param EntityRepository<EntityCollection<MailArchiveEntity>> $froshMailArchiveRepository
         */
        public function __construct(
            private readonly EntityRepository $froshMailArchiveRepository,
        ) {
        }

        public function collect(HealthCollection $collection): void
        {
            $criteria = new Criteria();
            $criteria->addFilter(new EqualsFilter('transportState', MailSender::TRANSPORT_STATE_FAILED));

            $count = $this->froshMailArchiveRepository->searchIds($criteria, new Context(new SystemSource()))->getTotal();

            $result = new SettingsResult();
            $result->assign([
                'id' => 'frosh_mail_archive_failed',
                'snippet' => 'Failed mails in MailArchive',
                'current' => (string) $count,
                'recommended' => '0',
                'state' => $count === 0 ? SettingsResult::GREEN : SettingsResult::ERROR,
            ]);

            $collection->add($result);
        }
    }
} else {
    class FroshToolsChecker
    {
    }
}
