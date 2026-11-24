<?php

declare(strict_types=1);

namespace Frosh\MailArchive\Tests\Services;

use Frosh\MailArchive\Services\PreviewImageSourceProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\System\SalesChannel\Aggregate\SalesChannelDomain\SalesChannelDomainCollection;
use Shopware\Core\System\SalesChannel\Aggregate\SalesChannelDomain\SalesChannelDomainEntity;
use Shopware\Core\Test\Stub\DataAbstractionLayer\StaticEntityRepository;

/**
 * @internal
 */
#[CoversClass(PreviewImageSourceProvider::class)]
class PreviewImageSourceProviderTest extends TestCase
{
    public function testReturnsUniqueOriginsOfShopDomainsAndPublicFilesystem(): void
    {
        $provider = $this->createProvider(
            ['https://Shop.example/en', 'https://shop.example/de', 'http://localhost:8000', 'default.headless0'],
            'https://cdn.example/media',
            'https://shop.example',
        );

        static::assertSame(
            ['https://cdn.example', 'https://shop.example', 'http://localhost:8000'],
            $provider->getSources(Context::createDefaultContext()),
        );
    }

    public function testConvertsInternationalDomainNamesToAscii(): void
    {
        $provider = $this->createProvider(['https://Bücher.example/de'], 'https://médias.example:8443/media', 'https://xn--bcher-kva.example');

        static::assertSame(
            ['https://xn--mdias-bsa.example:8443', 'https://xn--bcher-kva.example'],
            $provider->getSources(Context::createDefaultContext()),
        );
    }

    public function testSkipsUnconfiguredAndUnsafeUrls(): void
    {
        $provider = $this->createProvider(['javascript://shop.example', 'https://evil.example;script-src'], '', null);

        static::assertSame([], $provider->getSources(Context::createDefaultContext()));
    }

    /**
     * @param list<string> $domainUrls
     */
    private function createProvider(array $domainUrls, ?string $publicFilesystemUrl, ?string $appUrl): PreviewImageSourceProvider
    {
        $domains = new SalesChannelDomainCollection();
        foreach ($domainUrls as $index => $url) {
            $domain = new SalesChannelDomainEntity();
            $domain->setId('domain-' . $index);
            $domain->setUrl($url);
            $domains->add($domain);
        }

        /** @var StaticEntityRepository<SalesChannelDomainCollection> $repository */
        $repository = new StaticEntityRepository([$domains]);

        return new PreviewImageSourceProvider($repository, $publicFilesystemUrl, $appUrl);
    }
}
