<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\Filter;

use Oro\Bundle\ConsentBundle\Entity\Consent;
use Oro\Bundle\ConsentBundle\Entity\ConsentAcceptance;
use Oro\Bundle\ConsentBundle\Filter\FrontendConsentContentNodeValidFilter;
use Oro\Bundle\ConsentBundle\Provider\ConsentAcceptanceProvider;
use Oro\Bundle\ConsentBundle\Validator\ConsentContentNodeValidator;
use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Bundle\WebCatalogBundle\Provider\WebCatalogProvider;
use Psr\Log\LoggerInterface;

class FrontendConsentContentNodeValidFilterTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var FrontendHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $frontendHelper;

    /**
     * @var ConsentContentNodeValidator|\PHPUnit_Framework_MockObject_MockObject
     */
    private $contentNodeValidator;

    /**
     * @var FrontendConsentContentNodeValidFilter
     */
    private $filter;

    /**
     * @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $logger;

    /**
     * @var WebCatalogProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    private $webCatalogProvider;

    /**
     * @var WebsiteManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $websiteManager;

    /**
     * @var ConsentAcceptanceProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    private $consentAcceptanceProvider;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->frontendHelper = $this->createMock(FrontendHelper::class);
        $this->contentNodeValidator = $this->createMock(ConsentContentNodeValidator::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->webCatalogProvider = $this->createMock(WebCatalogProvider::class);
        $this->websiteManager = $this->createMock(WebsiteManager::class);
        $this->consentAcceptanceProvider = $this->createMock(ConsentAcceptanceProvider::class);
        $this->filter = new FrontendConsentContentNodeValidFilter(
            $this->webCatalogProvider,
            $this->logger,
            $this->websiteManager,
            $this->frontendHelper,
            $this->contentNodeValidator,
            $this->consentAcceptanceProvider
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset(
            $this->frontendHelper,
            $this->contentNodeValidator,
            $this->logger,
            $this->webCatalogProvider,
            $this->websiteManager,
            $this->filter
        );
    }

    /**
     * @dataProvider isConsentPassedFilterProvider
     *
     * @param Consent $consent
     * @param Website $currentWebsite
     * @param bool $isFrontendRequest
     * @param bool $contentNodeValidatorResult
     * @param WebCatalog|null $currentWebCatalog
     * @param bool $isErrorToLog
     * @param bool $expectedResult
     * @param ConsentAcceptance|null $consentAcceptance
     */
    public function testIsConsentPassedFilter(
        Consent $consent,
        Website $currentWebsite,
        bool $isFrontendRequest,
        bool $contentNodeValidatorResult,
        WebCatalog $currentWebCatalog = null,
        bool $isErrorToLog,
        bool $expectedResult,
        ConsentAcceptance $consentAcceptance = null
    ) {
        $this->frontendHelper->expects($this->any())
            ->method('isFrontendRequest')
            ->willReturn($isFrontendRequest);

        $this->consentAcceptanceProvider
            ->expects($this->any())
            ->method('getCustomerConsentAcceptanceByConsentId')
            ->with($consent->getId())
            ->willReturn($consentAcceptance);

        $this->webCatalogProvider
            ->expects($this->any())
            ->method('getWebCatalog')
            ->willReturn($currentWebCatalog);

        $this->websiteManager
            ->expects($this->any())
            ->method('getCurrentWebsite')
            ->willReturn($currentWebsite);

        if ($isErrorToLog) {
            $this->logger
                ->expects($this->once())
                ->method('error')
                ->with(
                    "Consent with id '999' point to the WebCatalog that doesn't use in the website with id '1'!"
                );
        } else {
            $this->logger
                ->expects($this->never())
                ->method('error');
        }

        $this->contentNodeValidator->expects($this->any())
            ->method('isValid')
            ->with($consent->getContentNode(), $consent)
            ->willReturn($contentNodeValidatorResult);

        $result = $this->filter->isConsentPassedFilter($consent);
        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @return array
     */
    public function isConsentPassedFilterProvider()
    {
        $contentNodeWebCatalog = $this->getEntity(WebCatalog::class, ['id' => 1]);
        $notContentNodeWebCatalog = $this->getEntity(WebCatalog::class, ['id' => 1]);

        return [
            "Content node isn't present" => [
                'consent' => $this->getEntity(Consent::class, ['id' => 999]),
                'currentWebsite' => $this->getEntity(Website::class, ['id' => 1]),
                'isFrontendRequest' => true,
                'contentNodeValidatorResult' => false,
                'currentWebCatalog' => $contentNodeWebCatalog,
                'isErrorToLog' => false,
                'expectedResult' => true
            ],
            "Is backend request" => [
                'consent' => $this->getEntity(Consent::class, ['id' => 999]),
                'currentWebsite' => $this->getEntity(Website::class, ['id' => 1]),
                'isFrontendRequest' => false,
                'contentNodeValidatorResult' => false,
                'currentWebCatalog' => $contentNodeWebCatalog,
                'isErrorToLog' => false,
                'expectedResult' => true
            ],
            "There is consent acceptance found by consent" => [
                'consent' => $this->getEntity(Consent::class, ['id' => 999]),
                'currentWebsite' => $this->getEntity(Website::class, ['id' => 1]),
                'isFrontendRequest' => true,
                'contentNodeValidatorResult' => false,
                'currentWebCatalog' => $contentNodeWebCatalog,
                'isErrorToLog' => false,
                'expectedResult' => true,
                'consentAcceptance' => $this->getEntity(ConsentAcceptance::class, ['id' => 1])
            ],
            "Web catalog not used in scope of current website" => [
                'consent' => $this->getEntity(
                    Consent::class,
                    [
                        'id' => 999,
                        'contentNode' => $this->getEntity(
                            ContentNode::class,
                            [
                                'webCatalog' => $contentNodeWebCatalog
                            ]
                        ),
                    ]
                ),
                'currentWebsite' => $this->getEntity(Website::class, ['id' => 1]),
                'isFrontendRequest' => true,
                'contentNodeValidatorResult' => true,
                'currentWebCatalog' => $notContentNodeWebCatalog,
                'isErrorToLog' => true,
                'expectedResult' => false
            ],
            "Content node validator returns invalid result" => [
                'consent' => $this->getEntity(
                    Consent::class,
                    [
                        'id' => 999,
                        'contentNode' => $this->getEntity(
                            ContentNode::class,
                            [
                                'webCatalog' => $contentNodeWebCatalog
                            ]
                        ),
                    ]
                ),
                'currentWebsite' => $this->getEntity(Website::class, ['id' => 1]),
                'isFrontendRequest' => true,
                'contentNodeValidatorResult' => false,
                'currentWebCatalog' => $contentNodeWebCatalog,
                'isErrorToLog' => false,
                'expectedResult' => false
            ],
            "Content node validator returns valid result" => [
                'consent' => $this->getEntity(
                    Consent::class,
                    [
                        'id' => 999,
                        'contentNode' => $this->getEntity(
                            ContentNode::class,
                            [
                                'webCatalog' => $contentNodeWebCatalog
                            ]
                        ),
                    ]
                ),
                'currentWebsite' => $this->getEntity(Website::class, ['id' => 1]),
                'isFrontendRequest' => true,
                'contentNodeValidatorResult' => true,
                'currentWebCatalog' => $contentNodeWebCatalog,
                'isErrorToLog' => false,
                'expectedResult' => true
            ]
        ];
    }
}
