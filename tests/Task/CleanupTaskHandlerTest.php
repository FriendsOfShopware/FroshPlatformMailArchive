<?php

declare(strict_types=1);

namespace Frosh\MailArchive\Tests\Task;

use Frosh\MailArchive\Messenger\CleanupMessage;
use Frosh\MailArchive\Task\CleanupTaskHandler;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskCollection;
use Shopware\Core\Test\Stub\DataAbstractionLayer\StaticEntityRepository;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * @internal
 */
#[Package('framework')]
#[CoversClass(CleanupTaskHandler::class)]
class CleanupTaskHandlerTest extends TestCase
{
    public function testRunDispatchesCleanupWithTheCurrentScheduledTaskConstructor(): void
    {
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->once())->method('dispatch')
            ->with(self::isInstanceOf(CleanupMessage::class))
            ->willReturn(new Envelope(new CleanupMessage()));
        $handler = new CleanupTaskHandler(
            StaticEntityRepository::of(ScheduledTaskCollection::class, []),
            $bus,
            new NullLogger(),
        );

        $handler->run();
    }

    public function testDispatchFailuresPropagateToTheScheduledTaskRunner(): void
    {
        $failure = new \RuntimeException('Queue unavailable');
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->once())->method('dispatch')->willThrowException($failure);
        $handler = new CleanupTaskHandler(
            StaticEntityRepository::of(ScheduledTaskCollection::class, []),
            $bus,
            new NullLogger(),
        );

        $this->expectExceptionObject($failure);

        $handler->run();
    }
}
