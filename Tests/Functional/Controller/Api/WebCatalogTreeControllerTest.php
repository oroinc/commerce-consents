<?php

namespace Oro\Bundle\ConsentBundle\Tests\Functional\Controller\Api\Rest;

use Oro\Bundle\ConsentBundle\Tests\Functional\Entity\ConsentFeatureTrait;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebCatalogBundle\Tests\Functional\DataFixtures\LoadContentNodesData;
use Oro\Bundle\WebCatalogBundle\Tests\Functional\DataFixtures\LoadWebCatalogData;

class WebCatalogTreeControllerTest extends WebTestCase
{
    use ConsentFeatureTrait;

    public function setUp()
    {
        $this->initClient([], $this->generateWsseAuthHeader());

        $this->loadFixtures([
            LoadContentNodesData::class,
        ]);

        $this->enableConsentFeature();
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        $this->disableConsentFeature();
    }

    public function testGet()
    {
        $webCatalog = $this->getReference(LoadWebCatalogData::CATALOG_1);
        $this->client->request(
            'GET',
            $this->getUrl('oro_api_consent_webcatalog_tree_get', ['webCatalog' => $webCatalog->getId()])
        );

        $response = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($response, 200);

        $jsonContent = json_decode($response->getContent(), true);
        $expectedContent = $this->getExpectedWebCatalogTree();

        $this->assertEquals($expectedContent, $jsonContent);
    }

    /**
     * @return array
     */
    public function getExpectedWebCatalogTree()
    {
        $contentNode1 = $this->getReference(LoadContentNodesData::CATALOG_1_ROOT);
        $contentNode2 = $this->getReference(LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_1);
        $contentNode3 = $this->getReference(LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_1_1);
        $contentNode4 = $this->getReference(LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_1_2);
        $contentNode5 = $this->getReference(LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_2);
        return [
            [
                'id' => $contentNode1->getId(),
                'parent' => '#',
                'text' => LoadContentNodesData::CATALOG_1_ROOT,
                'state' => ['opened' => true]
            ],
            [
                'id' => $contentNode2->getId(),
                'parent' => $contentNode2->getParentNode()->getId(),
                'text' => LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_1,
                'state' => ['opened' => false]
            ],
            [
                'id' => $contentNode3->getId(),
                'parent' => $contentNode3->getParentNode()->getId(),
                'text' => LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_1_1,
                'state' => ['opened' => false]
            ],
            [
                'id' => $contentNode4->getId(),
                'parent' => $contentNode4->getParentNode()->getId(),
                'text' => LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_1_2,
                'state' => ['opened' => false]
            ],
            [
                'id' => $contentNode5->getId(),
                'parent' => $contentNode5->getParentNode()->getId(),
                'text' => LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_2,
                'state' => ['opened' => false]
            ],
        ];
    }
}
