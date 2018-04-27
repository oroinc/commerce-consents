<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\Filter;

use Oro\Bundle\ConsentBundle\Entity\Consent;
use Oro\Bundle\ConsentBundle\Entity\ConsentAcceptance;
use Oro\Bundle\ConsentBundle\Filter\AdminConsentContentNodeValidFilter;
use Oro\Bundle\ConsentBundle\Provider\ConsentAcceptanceProvider;
use Oro\Bundle\ConsentBundle\Validator\ConsentContentNodeValidator;
use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Component\Testing\Unit\EntityTrait;

class AdminConsentContentNodeValidFilterTest extends \PHPUnit_Framework_TestCase
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
     * @var ConsentAcceptanceProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    private $consentAcceptanceProvider;

    /**
     * @var AdminConsentContentNodeValidFilter
     */
    private $filter;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->frontendHelper = $this->createMock(FrontendHelper::class);
        $this->contentNodeValidator = $this->createMock(ConsentContentNodeValidator::class);
        $this->consentAcceptanceProvider = $this->createMock(ConsentAcceptanceProvider::class);
        $this->filter = new AdminConsentContentNodeValidFilter(
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
            $this->filter
        );
    }

    /**
     * @dataProvider isConsentPassedFilterProvider
     *
     * @param Consent $consent
     * @param bool $isFrontendRequest
     * @param bool $contentNodeValidatorResult
     * @param bool $expectedResult
     * @param ConsentAcceptance|null $consentAcceptance
     */
    public function testIsConsentPassedFilter(
        Consent $consent,
        bool $isFrontendRequest,
        bool $contentNodeValidatorResult,
        bool $expectedResult,
        ConsentAcceptance $consentAcceptance = null
    ) {
        $this->frontendHelper->expects($this->any())
            ->method('isFrontendRequest')
            ->willReturn($isFrontendRequest);

        $this->consentAcceptanceProvider->expects($this->any())
            ->method('getCustomerConsentAcceptanceByConsentId')
            ->with($consent->getId())
            ->willReturn($consentAcceptance);

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
        return [
            "Content node isn't present" => [
                'consent' => $this->getEntity(Consent::class, ['id' => 999]),
                'isFrontendRequest' => false,
                'contentNodeValidatorResult' => false,
                'expectedResult' => true,

            ],
            "Is frontend request" => [
                'consent' => $this->getEntity(Consent::class, ['id' => 999]),
                'isFrontendRequest' => true,
                'contentNodeValidatorResult' => false,
                'expectedResult' => true,
            ],
            "There is consent acceptance found by consent" => [
                'consent' => $this->getEntity(Consent::class, ['id' => 999]),
                'isFrontendRequest' => true,
                'contentNodeValidatorResult' => false,
                'expectedResult' => true,
                'consentAcceptance' => $this->getEntity(ConsentAcceptance::class, ['id' => 1])
            ],
            "Content node validator return valid result" => [
                'consent' => $this->getEntity(
                    Consent::class,
                    [
                        'id' => 999,
                        'contentNode' => $this->getEntity(ContentNode::class)
                    ]
                ),
                'isFrontendRequest' => false,
                'contentNodeValidatorResult' => true,
                'expectedResult' => true,
            ],
            "Content node validator returns invalid result" => [
                'consent' => $this->getEntity(
                    Consent::class,
                    [
                        'id' => 999,
                        'contentNode' => $this->getEntity(ContentNode::class)
                    ]
                ),
                'isFrontendRequest' => false,
                'contentNodeValidatorResult' => false,
                'expectedResult' => false,
            ]
        ];
    }
}
