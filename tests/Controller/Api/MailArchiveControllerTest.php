<?php

declare(strict_types=1);

namespace Frosh\MailArchive\Tests\Controller\Api;

use Frosh\MailArchive\Acl\MailArchivePrivileges;
use Frosh\MailArchive\Content\MailArchive\MailArchiveEntity;
use Frosh\MailArchive\Content\MailArchive\MailArchiveException;
use Frosh\MailArchive\Controller\Api\MailArchiveController;
use Frosh\MailArchive\Services\EmlFileManager;
use Frosh\MailArchive\Services\ResendRecipientOverride;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Mail\Service\AbstractMailSender;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\PlatformRequest;
use Shopware\Core\Test\Stub\DataAbstractionLayer\StaticEntityRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;
use ZBateson\MailMimeParser\MailMimeParser;

/**
 * @internal
 */
#[CoversClass(MailArchiveController::class)]
class MailArchiveControllerTest extends TestCase
{
    public function testFetchEmlHeadersReadsCcAndBccWithoutAMainRequest(): void
    {
        $archive = new MailArchiveEntity();
        $archive->setId('018f8dd0865b7f37a99b37e811b91a09');
        $archive->setEmlPath('mails/archive.eml.gz');
        $message = (new MailMimeParser())->parse(implode("\r\n", [
            'From: sender@example.com',
            'To: recipient@example.com',
            'Cc: copy@example.com',
            'Bcc: hidden@example.com',
            'Subject: Archived mail',
            '',
            'Archived content',
        ]), false);
        $fileManager = $this->createMock(EmlFileManager::class);
        $fileManager->expects($this->once())->method('getEmlAsMessage')
            ->with('mails/archive.eml.gz')->willReturn($message);
        $controller = $this->createHeaderController([$archive], $fileManager);
        $request = new Request([], ['mailId' => $archive->getId()]);

        $response = $controller->fetchEmlHeaders($request, Context::createDefaultContext());
        $headers = json_decode((string) $response->getContent(), true, flags: \JSON_THROW_ON_ERROR);

        static::assertSame('copy@example.com', $headers['Cc']);
        static::assertSame('hidden@example.com', $headers['Bcc']);
        static::assertFalse($request->attributes->has(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_ID));
    }

    public function testFetchEmlHeadersReturnsEmptyHeadersForLegacyMail(): void
    {
        $archive = new MailArchiveEntity();
        $archive->setId('018f8dd0865b7f37a99b37e811b91a09');
        $fileManager = $this->createMock(EmlFileManager::class);
        $fileManager->expects($this->never())->method('getEmlAsMessage');
        $controller = $this->createHeaderController([$archive], $fileManager);

        $response = $controller->fetchEmlHeaders(
            new Request([], ['mailId' => $archive->getId()]),
            Context::createDefaultContext(),
        );

        static::assertSame('[]', $response->getContent());
    }

    public function testFetchEmlHeadersReturnsEmptyHeadersWhenFileCannotBeRead(): void
    {
        $archive = new MailArchiveEntity();
        $archive->setId('018f8dd0865b7f37a99b37e811b91a09');
        $archive->setEmlPath('mails/missing.eml.gz');
        $fileManager = $this->createMock(EmlFileManager::class);
        $fileManager->expects($this->once())->method('getEmlAsMessage')
            ->with('mails/missing.eml.gz')->willReturn(false);
        $controller = $this->createHeaderController([$archive], $fileManager);

        $response = $controller->fetchEmlHeaders(
            new Request([], ['mailId' => $archive->getId()]),
            Context::createDefaultContext(),
        );

        static::assertSame('[]', $response->getContent());
    }

    public function testFetchEmlHeadersRequiresMailId(): void
    {
        $controller = $this->createHeaderController([], $this->createStub(EmlFileManager::class));

        $this->expectExceptionObject(MailArchiveException::parameterMissing('mailId'));

        $controller->fetchEmlHeaders(new Request(), Context::createDefaultContext());
    }

    public function testFetchEmlHeadersRejectsUnknownMail(): void
    {
        $controller = $this->createHeaderController([], $this->createStub(EmlFileManager::class));

        $this->expectExceptionObject(MailArchiveException::notFound());

        $controller->fetchEmlHeaders(
            new Request([], ['mailId' => '018f8dd0865b7f37a99b37e811b91a09']),
            Context::createDefaultContext(),
        );
    }

    /**
     * @param list<MailArchiveEntity> $archives
     */
    private function createHeaderController(array $archives, EmlFileManager $fileManager): MailArchiveController
    {
        return new MailArchiveController(
            new StaticEntityRepository([new EntityCollection($archives)]),
            new StaticEntityRepository([]),
            $this->createStub(AbstractMailSender::class),
            new RequestStack(),
            $fileManager,
            new ResendRecipientOverride(),
        );
    }

    public function testResendPreservesArchivedHeadersAndReturnPath(): void
    {
        $archive = new MailArchiveEntity();
        $archive->setId('018f8dd0865b7f37a99b37e811b91a09');
        $archive->setEmlPath('mails/archive.eml.gz');
        $message = (new MailMimeParser())->parse(implode("\r\n", [
            'From: sender@example.com',
            'To: recipient@example.com',
            'Subject: Archived mail',
            'Return-Path: <bounce@example.com>',
            'Date: Sat, 17 Oct 2026 10:00:00 +0200',
            'X-Custom-Header: preserved',
            'MIME-Version: 1.0',
            'Content-Type: multipart/alternative; boundary="parts"',
            '',
            '--parts',
            'Content-Type: text/plain; charset=UTF-8',
            '',
            'Archived content',
            '--parts--',
        ]), false);
        $fileManager = $this->createMock(EmlFileManager::class);
        $fileManager->method('getEmlAsMessage')->willReturn($message);
        $sender = $this->createMock(AbstractMailSender::class);
        $sentEmail = null;
        $sender->expects($this->once())->method('send')->willReturnCallback(
            static function (Email $email) use (&$sentEmail): void {
                $sentEmail = $email;
            },
        );
        $request = new Request([], ['mailId' => $archive->getId()]);
        $requestStack = new RequestStack();
        $requestStack->push($request);
        $controller = new MailArchiveController(
            new StaticEntityRepository([new EntityCollection([$archive])]),
            new StaticEntityRepository([]),
            $sender,
            $requestStack,
            $fileManager,
            new ResendRecipientOverride(),
        );

        $controller->resend($request, Context::createDefaultContext());

        static::assertInstanceOf(Email::class, $sentEmail);
        static::assertSame('bounce@example.com', $sentEmail->getReturnPath()?->getAddress());
        static::assertSame('preserved', $sentEmail->getHeaders()->get('X-Custom-Header')?->getBodyAsString());
        static::assertSame('2026-10-17T10:00:00+02:00', $sentEmail->getDate()?->format(\DATE_ATOM));
        static::assertFalse($sentEmail->getHeaders()->has('Content-Type'));
        static::assertStringContainsString('Archived content', $sentEmail->getTextBody() ?? '');
    }

    /**
     * @param list<string> $expectedPrivileges
     */
    #[DataProvider('routeProvider')]
    public function testEveryActionRequiresPostAndExplicitPrivileges(string $method, array $expectedPrivileges): void
    {
        $attributes = (new \ReflectionMethod(MailArchiveController::class, $method))->getAttributes(Route::class);

        static::assertCount(1, $attributes);

        $route = $attributes[0]->newInstance();
        static::assertSame([Request::METHOD_POST], $route->methods);
        static::assertSame($expectedPrivileges, $route->defaults[PlatformRequest::ATTRIBUTE_ACL]);
    }

    /**
     * @return iterable<string, array{string, list<string>}>
     */
    public static function routeProvider(): iterable
    {
        yield 'EML headers require archive read access' => [
            'fetchEmlHeaders',
            [MailArchivePrivileges::READ],
        ];

        yield 'resend cannot be used by viewers' => [
            'resend',
            [MailArchivePrivileges::READ, MailArchivePrivileges::RESEND],
        ];

        yield 'EML download requires archive read access' => [
            'download',
            [MailArchivePrivileges::READ],
        ];

        yield 'attachment download requires archive and attachment access' => [
            'attachment',
            [MailArchivePrivileges::READ, MailArchivePrivileges::ATTACHMENT_READ],
        ];
    }
}
