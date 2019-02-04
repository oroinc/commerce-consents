<?php

namespace Oro\Bundle\ConsentBundle\GuestAccess\Provider;

use Oro\Bundle\ConsentBundle\Provider\ConsentDataProvider;
use Oro\Bundle\FrontendBundle\GuestAccess\Provider\GuestAccessAllowedUrlsProviderInterface;
use Oro\Bundle\RedirectBundle\Generator\CanonicalUrlGenerator;

/**
 * Provides a list of patterns for URLs for which an access is granted for non-authenticated visitors.
 */
class GuestAccessAllowedUrlsProvider implements GuestAccessAllowedUrlsProviderInterface
{
    /**
     * @var string[]
     */
    private $allowedUrls = [];

    /**
     * @var ConsentDataProvider
     */
    private $consentProvider;

    /**
     * @var CanonicalUrlGenerator
     */
    private $canonicalUrlGenerator;

    /**
     * @param ConsentDataProvider $consentProvider
     * @param CanonicalUrlGenerator $canonicalUrlGenerator
     */
    public function __construct(
        ConsentDataProvider $consentProvider,
        CanonicalUrlGenerator $canonicalUrlGenerator
    ) {
        $this->consentProvider = $consentProvider;
        $this->canonicalUrlGenerator = $canonicalUrlGenerator;
    }

    /**
     * Adds a pattern to the list of allowed URL patterns.
     *
     * @param string $pattern
     */
    public function addAllowedUrlPattern($pattern)
    {
        $this->allowedUrls[] = $pattern;
    }

    /**
     * {@inheritdoc}
     */
    public function getAllowedUrlsPatterns()
    {
        $allowedUrlsByConsents = [];
        $consents = $this->consentProvider->getAllConsentData();
        $domainUrl = $this->canonicalUrlGenerator->getAbsoluteUrl('/');
        foreach ($consents as $consent) {
            $cmsPageData = $consent->getCmsPageData();
            if (null === $cmsPageData) {
                continue;
            }
            $allowedUrlsByConsents[] = '^/' . \str_replace($domainUrl, '', $cmsPageData->getUrl()) . '$';
        }

        return \array_merge($this->allowedUrls, $allowedUrlsByConsents);
    }
}
