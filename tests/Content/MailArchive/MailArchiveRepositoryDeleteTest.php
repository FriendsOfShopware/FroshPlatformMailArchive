<?php

declare(strict_types=1);

namespace Frosh\MailArchive\Tests\Content\MailArchive;

use Doctrine\DBAL\Connection;
use Frosh\MailArchive\Content\MailArchive\MailArchiveDefinition;
use Frosh\MailArchive\Services\EmlFileManager;
use Frosh\MailArchive\Services\MailSender;
use Frosh\MailArchive\Subscriber\MailArchiveDeleteSubscriber;
use Frosh\MailArchive\Subscriber\MailArchiveWriteProtectionSubscriber;
use Frosh\MailArchive\Tests\ShopwareIntegration;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Test\TestCaseBase\KernelLifecycleManager;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * @internal
 */
#[CoversClass(MailArchiveWriteProtectionSubscriber::class)]
#[CoversClass(MailArchiveDeleteSubscriber::class)]
class MailArchiveRepositoryDeleteTest extends TestCase
{
    public function testCrudScopeDeletesArchiveAttachmentAndStoredEml(): void
    {
        if (!ShopwareIntegration::bootIfAvailable()) {
            $this->markTestSkipped('Shopware project with a test database is not available.');
        }

        $container = $this->testContainer();
        $connection = $container->get(Connection::class);
        static::assertInstanceOf(Connection::class, $connection);
        $connection->beginTransaction();

        try {
            $this->deleteStoredArchive($container);
        } finally {
            $connection->rollBack();
        }
    }

    private function deleteStoredArchive(ContainerInterface $container): void
    {
        $archiveRepository = $container->get('frosh_mail_archive.repository');
        $attachmentRepository = $container->get('frosh_mail_archive_attachment.repository');
        $files = $container->get(EmlFileManager::class);
        static::assertInstanceOf(EntityRepository::class, $archiveRepository);
        static::assertInstanceOf(EntityRepository::class, $attachmentRepository);
        static::assertInstanceOf(EmlFileManager::class, $files);

        $archiveId = Uuid::randomHex();
        $attachmentId = Uuid::randomHex();
        $message = "From: sender@example.com\r\nSubject: Archived mail\r\n\r\nHello";
        $emlPath = $files->writeFile($archiveId, $message);
        $context = Context::createDefaultContext();

        $archiveRepository->create([[
            'id' => $archiveId,
            'sender' => ['sender@example.com' => 'Sender'],
            'receiver' => ['recipient@example.com' => 'Recipient'],
            'subject' => 'Archived mail',
            'transportState' => MailSender::TRANSPORT_STATE_SENT,
            'emlPath' => $emlPath,
            'attachments' => [[
                'id' => $attachmentId,
                'fileName' => 'invoice.pdf',
                'contentType' => 'application/pdf',
                'fileSize' => 12,
            ]],
        ]], $context);

        static::assertSame($message, $files->getEmlFileAsString($emlPath));
        static::assertNotNull($archiveRepository->search(new Criteria([$archiveId]), $context)->getEntities()->first());

        try {
            $context->scope(Context::CRUD_API_SCOPE, function (Context $crudContext) use ($archiveRepository, $archiveId): void {
                $archiveRepository->update([[
                    'id' => $archiveId,
                    'subject' => 'changed',
                ]], $crudContext);
            });
            static::fail('Updates outside the system scope must stay forbidden.');
        } catch (AccessDeniedHttpException $exception) {
            static::assertStringContainsString(MailArchiveDefinition::ENTITY_NAME, $exception->getMessage());
        }

        $context->scope(Context::CRUD_API_SCOPE, function (Context $crudContext) use ($archiveRepository, $archiveId): void {
            $archiveRepository->delete([['id' => $archiveId]], $crudContext);
        });

        static::assertNull($archiveRepository->search(new Criteria([$archiveId]), $context)->getEntities()->first());
        static::assertNull($attachmentRepository->search(new Criteria([$attachmentId]), $context)->getEntities()->first());
        static::assertFalse($files->getEmlFileAsString($emlPath));
    }

    private function testContainer(): ContainerInterface
    {
        $container = KernelLifecycleManager::getKernel()->getContainer();
        if (!$container->has('test.service_container')) {
            static::fail('Shopware was not booted in the test environment.');
        }

        $testContainer = $container->get('test.service_container');
        static::assertInstanceOf(ContainerInterface::class, $testContainer);

        return $testContainer;
    }
}
