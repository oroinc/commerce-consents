<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\Helper;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CMSBundle\ContentVariantType\CmsPageContentVariantType;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\ConsentBundle\Entity\Consent;
use Oro\Bundle\ConsentBundle\Entity\ConsentAcceptance;
use Oro\Bundle\ConsentBundle\Helper\CmsPageHelper;
use Oro\Bundle\ConsentBundle\Provider\ConsentContextProvider;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentNode;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentVariant;
use Oro\Bundle\WebCatalogBundle\ContentNodeUtils\ContentNodeTreeResolverInterface;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Component\Testing\Unit\EntityTrait;

class CmsPageHelperTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var ContentNodeTreeResolverInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $contentNodeTreeResolver;

    /** @var ConsentContextProvider|\PHPUnit_Framework_MockObject_MockObject */
    private $consentContextProvider;

    /** @var CmsPageHelper */
    private $helper;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->contentNodeTreeResolver = $this->createMock(ContentNodeTreeResolverInterface::class);

        $this->consentContextProvider = $this->createMock(ConsentContextProvider::class);
        $this->consentContextProvider->expects($this->any())
            ->method('getScope')
            ->willReturn($this->getEntity(Scope::class, ['id' => 1]));

        $this->helper = new CmsPageHelper(
            $this->contentNodeTreeResolver,
            $this->consentContextProvider
        );
    }

    /**
     * @dataProvider buildProvider
     *
     * @param Consent $consent
     * @param ConsentAcceptance|null $consentAcceptance
     * @param bool $isAllowedToCallContentNodeTreeResolver
     * @param ResolvedContentVariant|null $resolvedContentVariant
     * @param Page|null $expected
     */
    public function testBuild(
        Consent $consent,
        ConsentAcceptance $consentAcceptance = null,
        bool $isAllowedToCallContentNodeTreeResolver,
        ResolvedContentVariant $resolvedContentVariant = null,
        Page $expected = null
    ) {
        if (!$isAllowedToCallContentNodeTreeResolver) {
            $this->contentNodeTreeResolver
                ->expects($this->never())
                ->method('getResolvedContentNode');
        } else {
            $resolvedContentNode = $this->createMock(ResolvedContentNode::class);
            $resolvedContentNode
                ->expects($this->atLeastOnce())
                ->method('getResolvedContentVariant')
                ->willReturn($resolvedContentVariant);

            $this->contentNodeTreeResolver
                ->expects($this->once())
                ->method('getResolvedContentNode')
                ->willReturn($resolvedContentNode);
        }

        $result = $this->helper->getCmsPage($consent, $consentAcceptance);
        $this->assertEquals($expected, $result);
    }

    /**
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function buildProvider()
    {
        $consentAcceptanceCmsPageId = 15;
        $resolvedContentVariantCmsPageId = 17;
        $fallbackValue = new LocalizedFallbackValue();
        $fallbackValue->setString('/cms-page-url-from-content-node');

        $consentWithContentNode = $this->getEntity(
            Consent::class,
            [
                'id' => 1,
                'contentNode' => $this->getEntity(
                    ContentNode::class,
                    [
                        'id' => 12,
                        'localizedUrls' => new ArrayCollection([$fallbackValue]),
                    ]
                ),
            ]
        );

        $consentWithoutContentNode = $this->getEntity(
            Consent::class,
            [
                'id' => 1
            ]
        );

        $consentAcceptance = $this->getEntity(
            ConsentAcceptance::class,
            [
                'id' => 13,
                'landingPage' => $this->getEntity(Page::class, ['id' => $consentAcceptanceCmsPageId]),
            ]
        );

        $consentAcceptanceWithoutLanding = $this->getEntity(
            ConsentAcceptance::class,
            [
                'id' => 13,
            ]
        );

        $contentVariantWithCmsType = new ResolvedContentVariant();
        $contentVariantWithCmsType->setData([
            'cms_page' => $this->getEntity(Page::class, ['id' => $resolvedContentVariantCmsPageId])
        ]);
        $contentVariantWithCmsType->setType(CmsPageContentVariantType::TYPE);

        $contentVariantWithInvalidType = new ResolvedContentVariant();
        $contentVariantWithInvalidType->setData([
            'cms_page' => $this->getEntity(Page::class, ['id' => $resolvedContentVariantCmsPageId])
        ]);
        $contentVariantWithInvalidType->setType('incorrect_type');

        return [
            "Consent acceptance is set and it has a landing page" => [
                'consent' => $consentWithContentNode,
                'consentAcceptance' => $consentAcceptance,
                'isAllowedToCallContentNodeTreeResolver' => false,
                'resolvedContentVariant' => null,
                'expected' => $this->getEntity(Page::class, ['id' => $consentAcceptanceCmsPageId])
            ],
            "Consent acceptance is set and it doesn't have a landing page" => [
                'consent' => $consentWithoutContentNode,
                'consentAcceptance' => $consentAcceptanceWithoutLanding,
                'isAllowedToCallContentNodeTreeResolver' => false,
                'resolvedContentVariant' => null,
                'expected' => null
            ],
            "Consent acceptance isn't set and consent doesn't have a content node" => [
                'consent' => $consentWithoutContentNode,
                'consentAcceptance' => null,
                'isAllowedToCallContentNodeTreeResolver' => false,
                'resolvedContentVariant' => null,
                'expected' => null
            ],
            "Consent acceptance isn't set and consent has incorrect content variant type" => [
                'consent'  => $consentWithContentNode,
                'consentAcceptance' => null,
                'isAllowedToCallContentNodeTreeResolver' => true,
                'resolvedContentVariant' => $contentVariantWithInvalidType,
                'expected' => null
            ],
            "Consent acceptance isn't set and consent has correct content variant type" => [
                'consent' => $consentWithContentNode,
                'consentAcceptance' => null,
                'isAllowedToCallContentNodeTreeResolver' => true,
                'resolvedContentVariant' => $contentVariantWithCmsType,
                'expected' => $this->getEntity(Page::class, ['id' => $resolvedContentVariantCmsPageId])
            ],
        ];
    }
}
