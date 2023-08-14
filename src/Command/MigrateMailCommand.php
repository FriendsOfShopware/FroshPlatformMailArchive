<?php declare(strict_types=1);

namespace Frosh\MailArchive\Command;

use Frosh\MailArchive\MessageQueue\MigrateMailHandler;
use Frosh\MailArchive\MessageQueue\MigrateMailMessage;
use Shopware\Core\Framework\DataAbstractionLayer\Dbal\Common\IteratorFactory;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand('frosh:mailarchive:migrate', 'Migrate mails from database to private filesystem')]
class MigrateMailCommand extends Command
{
    public function __construct(
        private readonly MigrateMailHandler $migrateMailHandler,
        private readonly MessageBusInterface $messageBus,
        private readonly EntityRepository $froshMailArchiveRepository,
        private readonly IteratorFactory $iteratorFactory
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('sync', null, InputOption::VALUE_NONE, 'Migrate mails synchronously without message queue');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $iterator = $this->iteratorFactory->createIterator($this->froshMailArchiveRepository->getDefinition());

        $count = $iterator->fetchCount();

        if ($count === 0) {
            $output->writeln('No mails to migrate');

            return Command::SUCCESS;
        }

        $progressBar = new ProgressBar($output, $count);
        $progressBar->start();

        while ($ids = $iterator->fetch()) {
            $message = new MigrateMailMessage(\array_values($ids));

            if ($input->getOption('sync')) {
                $this->migrateMailHandler->__invoke($message);
            } else {
                $this->messageBus->dispatch($message);
            }

            $progressBar->advance(\count($ids));
        }

        $progressBar->finish();

        $output->writeln('');

        return Command::SUCCESS;
    }
}
