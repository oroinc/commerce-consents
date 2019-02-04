<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\GuestAccess\Provider;

use Oro\Bundle\ConsentBundle\GuestAccess\Provider\GuestAccessAllowedUrlsProvider;
use Oro\Bundle\ConsentBundle\Model\CmsPageData;
use Oro\Bundle\ConsentBundle\Model\ConsentData;
use Oro\Bundle\ConsentBundle\Provider\ConsentDataProvider;
use Oro\Bundle\RedirectBundle\Generator\CanonicalUrlGenerator;

class GuestAccessAllowedUrlsProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var ConsentDataProvider|\PHPUnit_Framework_MockObject_MockObject */
    private $consentProvider;

    /** @var CanonicalUrlGenerator|\PHPUnit_Framework_MockObject_MockObject */
    private $canonicalUrlGenerator;

    /** @var GuestAccessAllowedUrlsProvider */
    private $guestAccessAllowedUrlsProvider;

    protected function setUp()
    {
        $this->consentProvider = $this->createMock(ConsentDataProvider::class);
        $this->canonicalUrlGenerator = $this->createMock(CanonicalUrlGenerator::class);

        $this->guestAccessAllowedUrlsProvider = new GuestAccessAllowedUrlsProvider(
            $this->consentProvider,
            $this->canonicalUrlGenerator
        );
    }

    public function testGetAllowedUrlsPatternsWithoutConsents()
    {
        $pattern = '^/consent-pattern$';

        $this->consentProvider->expects($this->once())
            ->method('getAllConsentData')
            ->willReturn([]);

        $this->guestAccessAllowedUrlsProvider->addAllowedUrlPattern($pattern);

        $this->assertEquals([$pattern], $this->guestAccessAllowedUrlsProvider->getAllowedUrlsPatterns());
    }

    public function testGetAllowedUrlsPatterns()
    {
        $urlA = 'url-1';
        $urlB = 'url-2';
        $patternC = '^/consent-pattern$';
        $expectedUrls = [$patternC, '^/' . $urlA . '$', '^/' . $urlB . '$'];

        $baseUrl = '/index_dev.php/';
        $cmsPageDataA = $this->createMock(CmsPageData::class);
        $cmsPageDataA->expects($this->once())
            ->method('getUrl')
            ->willReturn($baseUrl . $urlA);
        $cmsPageDataB = $this->createMock(CmsPageData::class);
        $cmsPageDataB->expects($this->once())
            ->method('getUrl')
            ->willReturn($urlB);

        $consentDataA = $this->createMock(ConsentData::class);
        $consentDataA->expects($this->once())
            ->method('getCmsPageData')
            ->willReturn($cmsPageDataA);
        $consentDataB = $this->createMock(ConsentData::class);
        $consentDataB->expects($this->once())
            ->method('getCmsPageData')
            ->willReturn($cmsPageDataB);
        $consentDataC = $this->createMock(ConsentData::class);
        $this->consentProvider->expects($this->once())
            ->method('getAllConsentData')
            ->willReturn([$consentDataA, $consentDataB, $consentDataC]);

        $this->canonicalUrlGenerator->expects($this->once())
            ->method('getAbsoluteUrl')
            ->with('/')
            ->willReturn($baseUrl);

        $this->guestAccessAllowedUrlsProvider->addAllowedUrlPattern($patternC);

        $this->assertEquals($expectedUrls, $this->guestAccessAllowedUrlsProvider->getAllowedUrlsPatterns());
    }
}
