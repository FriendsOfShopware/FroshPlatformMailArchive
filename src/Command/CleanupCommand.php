<?php

declare(strict_types=1);

namespace Frosh\MailArchive\Command;

use Frosh\MailArchive\Messenger\CleanupMessage;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand('frosh:mail-archive:cleanup', 'Cleanup old mail archive entries')]
class CleanupCommand extends Command
{
    public function __construct(
        private readonly MessageBusInterface $messageBus,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Dispatching mail archive cleanup...');

        $this->messageBus->dispatch(new CleanupMessage());

        $output->writeln('Cleanup message dispatched successfully.');

        return Command::SUCCESS;
    }
}
