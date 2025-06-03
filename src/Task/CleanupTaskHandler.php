<?php

declare(strict_types=1);

namespace Frosh\MailArchive\Task;

use Frosh\MailArchive\Messenger\CleanupMessage;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskCollection;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler(handles: CleanupTask::class)]
class CleanupTaskHandler extends ScheduledTaskHandler
{
    /**
     * @param EntityRepository<ScheduledTaskCollection> $scheduledTaskRepository
     */
    public function __construct(
        EntityRepository $scheduledTaskRepository,
        private readonly MessageBusInterface $messageBus,
    ) {
        parent::__construct($scheduledTaskRepository);
    }

    public function run(): void
    {
        $this->messageBus->dispatch(new CleanupMessage());
    }
}
