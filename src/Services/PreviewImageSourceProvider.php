<?php

declare(strict_types=1);

namespace Frosh\MailArchive\Services;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\Aggregate\SalesChannelDomain\SalesChannelDomainCollection;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Collects the origins the shop itself serves images from. Only those are allowed in the mail preview,
 * so that third party resources like tracking pixels stay blocked.
 */
final class PreviewImageSourceProvider
{
    /**
     * @param EntityRepository<SalesChannelDomainCollection> $salesChannelDomainRepository
     */
    public function __construct(
        private readonly EntityRepository $salesChannelDomainRepository,
        #[Autowire(param: 'shopware.filesystem.public.url')]
        private readonly ?string $publicFilesystemUrl,
        #[Autowire(env: 'default::APP_URL')]
        private readonly ?string $appUrl,
    ) {
    }

    /**
     * @return list<string>
     */
    public function getSources(Context $context): array
    {
        $urls = [$this->publicFilesystemUrl, $this->appUrl];

        $criteria = new Criteria();
        $domains = $this->salesChannelDomainRepository->search($criteria, $context)->getEntities();

        foreach ($domains as $domain) {
            $urls[] = $domain->getUrl();
        }

        $sources = [];
        foreach ($urls as $url) {
            $origin = $this->getOrigin($url);

            if ($origin !== null) {
                $sources[$origin] = true;
            }
        }

        return array_keys($sources);
    }

    private function getOrigin(?string $url): ?string
    {
        if ($url === null || $url === '') {
            return null;
        }

        $parts = parse_url($url);
        if ($parts === false || !isset($parts['scheme'], $parts['host'])) {
            return null;
        }

        $scheme = strtolower($parts['scheme']);
        // international domain names are only valid in their ASCII form inside a content security policy
        $host = idn_to_ascii($parts['host'], \IDNA_DEFAULT, \INTL_IDNA_VARIANT_UTS46);

        // headless domains like "default.headless0" have no scheme and are skipped above
        if (!\in_array($scheme, ['http', 'https'], true) || $host === false || preg_match('/^[a-z0-9.-]+$/', $host) !== 1) {
            return null;
        }

        return $scheme . '://' . $host . (isset($parts['port']) ? ':' . $parts['port'] : '');
    }
}
