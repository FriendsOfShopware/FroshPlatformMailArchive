<?php

declare(strict_types=1);

namespace Frosh\MailArchive\Subscriber;

use Frosh\MailArchive\Content\MailArchive\MailArchiveAttachmentDefinition;
use Frosh\MailArchive\Content\MailArchive\MailArchiveDefinition;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Command\DeleteCommand;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Command\SetNullOnDeleteCommand;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Command\WriteCommand;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Validation\PreWriteValidationEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class MailArchiveWriteProtectionSubscriber implements EventSubscriberInterface
{
    /**
     * @var list<string>
     */
    private const PROTECTED_ENTITIES = [
        MailArchiveDefinition::ENTITY_NAME,
        MailArchiveAttachmentDefinition::ENTITY_NAME,
    ];

    public static function getSubscribedEvents(): array
    {
        return [
            PreWriteValidationEvent::class => 'validateWriteCommands',
        ];
    }

    public function validateWriteCommands(PreWriteValidationEvent $event): void
    {
        if ($event->getContext()->getScope() === Context::SYSTEM_SCOPE) {
            return;
        }

        foreach ($event->getCommands() as $command) {
            if (!$this->isProtectedWrite($command)) {
                continue;
            }

            throw new AccessDeniedHttpException(\sprintf(
                'Write access to entity "%s" are not allowed in scope "%s".',
                $command->getEntityName(),
                $event->getContext()->getScope(),
            ));
        }
    }

    private function isProtectedWrite(WriteCommand $command): bool
    {
        // Deletes stay available for the administration. Referential cleanup that
        // Shopware emits while deleting a related record is not a user write.
        if (
            $command instanceof DeleteCommand
            || $command instanceof SetNullOnDeleteCommand
            || !$command->isValid()
        ) {
            return false;
        }

        return \in_array($command->getEntityName(), self::PROTECTED_ENTITIES, true);
    }
}
