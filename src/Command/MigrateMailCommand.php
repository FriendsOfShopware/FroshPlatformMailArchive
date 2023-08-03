<?php declare(strict_types=1);

namespace Frosh\MailArchive\Command;

use Frosh\MailArchive\MessageQueue\MigrateMailHandler;
use Frosh\MailArchive\MessageQueue\MigrateMailMessage;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand('frosh-mailarchive:migrate-mails', 'Migrate mails from database to private filesystem')]
class MigrateMailCommand extends Command
{
    public function __construct(
        private readonly MigrateMailHandler $migrateMailHandler,
        private readonly MessageBusInterface $messageBus,
        private readonly EntityRepository $froshMailArchiveRepository
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('sync', null, InputOption::VALUE_NONE, 'Migrate mails synchronously without message queue');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('emlPath', null));

        $ids = $this->froshMailArchiveRepository->searchIds($criteria, Context::createDefaultContext())->getIds();

        if (\count($ids) === 0) {
            $output->writeln('No mails to migrate');

            return Command::SUCCESS;
        }

        $progressBar = new ProgressBar($output, \count($ids));
        $progressBar->start();

        foreach (array_chunk($ids, 50) as $chunkedIds) {
            $message = new MigrateMailMessage($chunkedIds);

            if ($input->getOption('sync')) {
                $this->migrateMailHandler->__invoke($message);
            } else {
                $this->messageBus->dispatch($message);
            }

            $progressBar->advance(\count($chunkedIds));
        }

        $progressBar->finish();

        $output->writeln('');

        return Command::SUCCESS;
    }
}
