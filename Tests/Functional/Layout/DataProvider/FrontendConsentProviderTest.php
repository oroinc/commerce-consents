<?php

namespace Oro\Bundle\ConsentBundle\Tests\Layout\DataProvider;

use Oro\Bundle\ConsentBundle\Layout\DataProvider\FrontendConsentProvider;
use Oro\Bundle\ConsentBundle\Model\ConsentData;
use Oro\Bundle\ConsentBundle\Tests\Functional\DataFixtures\LoadConsentConfigData;
use Oro\Bundle\ConsentBundle\Tests\Functional\DataFixtures\LoadConsentsData;
use Oro\Bundle\ConsentBundle\Tests\Functional\DataFixtures\LoadCustomerUserData;
use Oro\Bundle\ConsentBundle\Tests\Functional\DataFixtures\LoadScopeData;
use Oro\Bundle\ConsentBundle\Tests\Functional\Entity\ConsentFeatureTrait;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;

/**
 * @covers \Oro\Bundle\ConsentBundle\Layout\DataProvider\FrontendConsentProvider
 * @covers \Oro\Bundle\ConsentBundle\Provider\ConsentDataProvider
 */
class FrontendConsentProviderTest extends WebTestCase
{
    use ConsentFeatureTrait;

    /**
     * @var FrontendConsentProvider
     */
    private $provider;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->loadFixtures([
            LoadConsentConfigData::class
        ]);

        $this->initFrontendRequest();
        $this->enableConsentFeature();

        $this->provider = $this->getContainer()->get('oro_consent.layout.data_provider.consent');
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        $this->getContainer()
            ->get('oro_consent.provider.consent_context_provider')
            ->resetContext();

        unset($this->provider);
    }

    /**
     * @dataProvider getAllConsentDataProvider
     *
     * @param string $customerUserReference
     * @param array  $expectedConsentReferences
     */
    public function testGetAllConsentData(string $customerUserReference, array $expectedConsentReferences)
    {
        /** @var CustomerUser $customerUserData */
        $customerUserData = $this->getReference($customerUserReference);

        $consentData = $this->provider->getAllConsentData($customerUserData);
        $this->assertExpectedConsents($consentData, $expectedConsentReferences);
    }

    /**
     * @return array
     */
    public function getAllConsentDataProvider()
    {
        return [
            "Customer with consent acceptances" => [
                'customerUserReference' => LoadCustomerUserData::EMAIL,
                'expectedConsentReferences' => [
                    LoadConsentsData::CONSENT_OPTIONAL_NODE1_WITH_CMS,
                    LoadConsentsData::CONSENT_OPTIONAL_NODE1_WITH_SYSTEM,
                    LoadConsentsData::CONSENT_OPTIONAL_NODE2_WITH_CMS,
                    LoadConsentsData::CONSENT_REQUIRED_NODE1_WITH_CMS,
                    LoadConsentsData::CONSENT_REQUIRED_NODE1_WITH_SYSTEM,
                    LoadConsentsData::CONSENT_REQUIRED_NODE2_WITH_CMS,
                    LoadConsentsData::CONSENT_OPTIONAL_WITHOUT_NODE,
                    LoadConsentsData::CONSENT_REQUIRED_WITHOUT_NODE
                ]
            ],
            "Customer without consent acceptances" => [
                'customerUserReference' => LoadCustomerUserData::LEVEL_1_EMAIL,
                'expectedConsentReferences' => [
                    LoadConsentsData::CONSENT_OPTIONAL_NODE1_WITH_CMS,
                    LoadConsentsData::CONSENT_REQUIRED_NODE1_WITH_CMS,
                    LoadConsentsData::CONSENT_OPTIONAL_WITHOUT_NODE,
                    LoadConsentsData::CONSENT_REQUIRED_WITHOUT_NODE,
                ]
            ],
        ];
    }

    /**
     * @dataProvider getRequiredConsentDataProvider
     *
     * @param string $customerUserReference
     * @param array  $expectedConsentReferences
     */
    public function testGetRequiredConsentData(
        string $customerUserReference,
        array $expectedConsentReferences
    ) {
        /** @var CustomerUser $customerUserData */
        $customerUserData = $this->getReference($customerUserReference);

        $consentData = $this->provider->getRequiredConsentData($customerUserData);
        $this->assertExpectedConsents($consentData, $expectedConsentReferences);
    }

    /**
     * @return array
     */
    public function getRequiredConsentDataProvider()
    {
        return [
            "Customer with consent acceptances" => [
                'customerUserReference' => LoadCustomerUserData::EMAIL,
                'expectedConsentReferences' => [
                    LoadConsentsData::CONSENT_REQUIRED_NODE1_WITH_CMS,
                    LoadConsentsData::CONSENT_REQUIRED_NODE1_WITH_SYSTEM,
                    LoadConsentsData::CONSENT_REQUIRED_NODE2_WITH_CMS,
                    LoadConsentsData::CONSENT_REQUIRED_WITHOUT_NODE
                ]
            ],
            "Customer without consent acceptances" => [
                'customerUserReference' => LoadCustomerUserData::LEVEL_1_EMAIL,
                'expectedConsentReferences' => [
                    LoadConsentsData::CONSENT_REQUIRED_NODE1_WITH_CMS,
                    LoadConsentsData::CONSENT_REQUIRED_WITHOUT_NODE,
                ]
            ],
        ];
    }

    /**
     * @dataProvider getAcceptedConsentDataProvider
     *
     * @param string $customerUserReference
     * @param array  $expectedConsentReferences
     */
    public function testGetAcceptedConsentData(
        string $customerUserReference,
        array $expectedConsentReferences
    ) {
        /** @var CustomerUser $customerUserData */
        $customerUserData = $this->getReference($customerUserReference);

        $consentData = $this->provider->getAcceptedConsentData($customerUserData);
        $this->assertExpectedConsents($consentData, $expectedConsentReferences);
    }

    /**
     * @return array
     */
    public function getAcceptedConsentDataProvider()
    {
        return [
            "Customer with consent acceptances" => [
                'customerUserReference' => LoadCustomerUserData::EMAIL,
                'expectedConsentReferences' => [
                    LoadConsentsData::CONSENT_OPTIONAL_NODE1_WITH_CMS,
                    LoadConsentsData::CONSENT_OPTIONAL_NODE1_WITH_SYSTEM,
                    LoadConsentsData::CONSENT_OPTIONAL_NODE2_WITH_CMS,
                    LoadConsentsData::CONSENT_REQUIRED_NODE1_WITH_CMS,
                    LoadConsentsData::CONSENT_REQUIRED_NODE1_WITH_SYSTEM,
                    LoadConsentsData::CONSENT_REQUIRED_NODE2_WITH_CMS,
                    LoadConsentsData::CONSENT_OPTIONAL_WITHOUT_NODE,
                    LoadConsentsData::CONSENT_REQUIRED_WITHOUT_NODE,
                ]
            ],
            "Customer without consent acceptances" => [
                'customerUserReference' => LoadCustomerUserData::LEVEL_1_EMAIL,
                'expectedConsentReferences' => []
            ],
        ];
    }

    /**
     * @dataProvider getNotAcceptedRequiredConsentDataProvider
     *
     * @param string $customerUserReference
     * @param array  $expectedConsentReferences
     */
    public function testGetNotAcceptedRequiredConsentData(
        string $customerUserReference,
        array $expectedConsentReferences
    ) {
        /** @var CustomerUser $customerUserData */
        $customerUserData = $this->getReference($customerUserReference);

        $consentData = $this->provider->getNotAcceptedRequiredConsentData($customerUserData);
        $this->assertExpectedConsents($consentData, $expectedConsentReferences);
    }

    /**
     * @return array
     */
    public function getNotAcceptedRequiredConsentDataProvider()
    {
        return [
            "Customer with consent acceptances" => [
                'customerUserReference' => LoadCustomerUserData::EMAIL,
                'expectedConsentReferences' => []
            ],
            "Customer without consent acceptances" => [
                'customerUserReference' => LoadCustomerUserData::LEVEL_1_EMAIL,
                'expectedConsentReferences' => [
                    LoadConsentsData::CONSENT_REQUIRED_NODE1_WITH_CMS,
                    LoadConsentsData::CONSENT_REQUIRED_WITHOUT_NODE,
                ]
            ],
        ];
    }

    /**
     * @param ConsentData[] $consentData
     * @param array $expectedConsentReferences
     */
    private function assertExpectedConsents(array $consentData, array $expectedConsentReferences)
    {
        $expectedConsentIds = array_map(function ($consentReference) {
            return $this->getReference($consentReference)->getId();
        }, $expectedConsentReferences);

        $consentIds = array_map(function (ConsentData $consent) {
            return $consent->getId();
        }, $consentData);

        $this->assertSame($expectedConsentIds, $consentIds);
    }

    /**
     * Prepare and set request object to the request stack
     */
    private function initFrontendRequest()
    {
        $request = Request::create('');
        $request->attributes = new ParameterBag(
            [
                '_web_content_scope' => $this->getReference(LoadScopeData::CATALOG_1_SCOPE)
            ]
        );
        $this->getContainer()->get('request_stack')->push($request);
    }
}
