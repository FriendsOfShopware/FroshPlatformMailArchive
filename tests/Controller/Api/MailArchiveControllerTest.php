<?php

declare(strict_types=1);

namespace Frosh\MailArchive\Tests\Controller\Api;

use Frosh\MailArchive\Acl\MailArchivePrivileges;
use Frosh\MailArchive\Controller\Api\MailArchiveController;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shopware\Core\PlatformRequest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @internal
 */
#[CoversClass(MailArchiveController::class)]
class MailArchiveControllerTest extends TestCase
{
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
