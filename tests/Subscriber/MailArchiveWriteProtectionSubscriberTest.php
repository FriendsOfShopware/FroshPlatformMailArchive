<?php

declare(strict_types=1);

namespace Frosh\MailArchive\Tests\Subscriber;

use Frosh\MailArchive\Content\MailArchive\MailArchiveAttachmentDefinition;
use Frosh\MailArchive\Content\MailArchive\MailArchiveDefinition;
use Frosh\MailArchive\Subscriber\MailArchiveWriteProtectionSubscriber;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Command\DeleteCommand;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Command\InsertCommand;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Command\SetNullOnDeleteCommand;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Command\UpdateCommand;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Command\WriteCommand;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Validation\PreWriteValidationEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * @internal
 */
#[CoversClass(MailArchiveWriteProtectionSubscriber::class)]
class MailArchiveWriteProtectionSubscriberTest extends TestCase
{
    public function testSystemScopeAllowsInsertAndUpdate(): void
    {
        $this->expectNotToPerformAssertions();

        $this->validate($this->event(Context::SYSTEM_SCOPE, [
            $this->command(InsertCommand::class, MailArchiveDefinition::ENTITY_NAME),
            $this->command(UpdateCommand::class, MailArchiveAttachmentDefinition::ENTITY_NAME),
        ]));
    }

    public function testCrudScopeRejectsInsertAndUpdate(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage(
            'Write access to entity "frosh_mail_archive_attachment" are not allowed in scope "crud".',
        );

        $this->validate($this->event(Context::CRUD_API_SCOPE, [
            $this->command(DeleteCommand::class, MailArchiveDefinition::ENTITY_NAME),
            $this->command(UpdateCommand::class, MailArchiveAttachmentDefinition::ENTITY_NAME),
        ]));
    }

    public function testCrudScopeRejectsArchiveInsert(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage(
            'Write access to entity "frosh_mail_archive" are not allowed in scope "crud".',
        );

        $this->validate($this->event(Context::CRUD_API_SCOPE, [
            $this->command(InsertCommand::class, MailArchiveDefinition::ENTITY_NAME),
        ]));
    }

    public function testCrudScopeAllowsDeleteAndReferentialCleanup(): void
    {
        $this->expectNotToPerformAssertions();

        $this->validate($this->event(Context::CRUD_API_SCOPE, [
            $this->command(DeleteCommand::class, MailArchiveDefinition::ENTITY_NAME),
            $this->command(DeleteCommand::class, MailArchiveAttachmentDefinition::ENTITY_NAME),
            $this->command(SetNullOnDeleteCommand::class, MailArchiveDefinition::ENTITY_NAME),
            $this->command(InsertCommand::class, 'product'),
            $this->command(UpdateCommand::class, MailArchiveDefinition::ENTITY_NAME, false),
        ]));
    }

    /**
     * @param class-string<WriteCommand> $commandClass
     */
    private function command(string $commandClass, string $entityName, bool $valid = true): WriteCommand
    {
        $command = $this->createMock($commandClass);
        $command->method('getEntityName')->willReturn($entityName);
        $command->method('isValid')->willReturn($valid);

        return $command;
    }

    /**
     * @param list<WriteCommand> $commands
     */
    private function event(string $scope, array $commands): PreWriteValidationEvent
    {
        $context = $this->createMock(Context::class);
        $context->method('getScope')->willReturn($scope);

        $event = $this->createMock(PreWriteValidationEvent::class);
        $event->method('getContext')->willReturn($context);
        $event->method('getCommands')->willReturn($commands);

        return $event;
    }

    private function validate(PreWriteValidationEvent $event): void
    {
        (new MailArchiveWriteProtectionSubscriber())->validateWriteCommands($event);
    }
}
