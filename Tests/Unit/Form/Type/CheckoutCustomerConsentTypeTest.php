<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ConsentBundle\Form\Type\CheckoutCustomerConsentsType;
use Oro\Bundle\ConsentBundle\Helper\ConsentContextInitializeHelperInterface;
use Oro\Bundle\ConsentBundle\Provider\ConsentAcceptanceProvider;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Symfony\Component\Form\FormBuilderInterface;

class CheckoutCustomerConsentTypeTest extends FormIntegrationTestCase
{
    use EntityTrait;

    const FEATURE_NAME = 'consents';
    const CUSTOMER_ACCEPTANCE_DATA = 'customer_acceptance_data';

    /** @var CheckoutCustomerConsentsType */
    private $formType;

    /** @var ConsentContextInitializeHelperInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $consentContextInitializeHelper;

    /** @var FeatureChecker|\PHPUnit_Framework_MockObject_MockObject */
    private $featureChecker;

    /** @var FormBuilderInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $builder;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->consentContextInitializeHelper = $this->createMock(
            ConsentContextInitializeHelperInterface::class
        );
        /** @var ConsentAcceptanceProvider|\PHPUnit_Framework_MockObject_MockObject $consentAcceptanceProvider */
        $consentAcceptanceProvider = $this->createMock(ConsentAcceptanceProvider::class);
        $consentAcceptanceProvider
            ->expects($this->any())
            ->method('getCustomerConsentAcceptances')
            ->willReturn(self::CUSTOMER_ACCEPTANCE_DATA);

        $this->builder = $this->createMock(FormBuilderInterface::class);

        $this->formType = new CheckoutCustomerConsentsType(
            $this->consentContextInitializeHelper,
            $consentAcceptanceProvider
        );

        $this->featureChecker = $this->createMock(FeatureChecker::class);
        $this->formType->setFeatureChecker($this->featureChecker);
        $this->formType->addFeature(self::FEATURE_NAME);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->consentContextInitializeHelper);
        unset($this->formType);
        unset($this->builder);
        unset($this->featureChecker);

        parent::tearDown();
    }

    public function testBuildFormWithFeatureDisabled()
    {
        $this->featureChecker
            ->expects($this->once())
            ->method('isFeatureEnabled')
            ->with(self::FEATURE_NAME, null)
            ->willReturn(false);

        $this->consentContextInitializeHelper
            ->expects($this->never())
            ->method('initialize');

        $this->builder
            ->expects($this->never())
            ->method('setData');

        $this->formType->buildForm($this->builder, []);
    }

    /**
     * @dataProvider buildFormWithFeatureEnabledProvider
     *
     * @param array $options
     * @param bool  $expectedContextWillBeInitializer
     * @param bool  $expectedSetData
     */
    public function testBuildFormWithFeatureEnabled(
        array $options,
        bool $expectedContextWillBeInitializer,
        bool $expectedSetData
    ) {
        $this->featureChecker
            ->expects($this->once())
            ->method('isFeatureEnabled')
            ->with(self::FEATURE_NAME, null)
            ->willReturn(true);

        if ($expectedContextWillBeInitializer) {
            $this->consentContextInitializeHelper
                ->expects($this->once())
                ->method('initialize')
                ->with($options['customerUser']);
        } else {
            $this->consentContextInitializeHelper
                ->expects($this->never())
                ->method('initialize');
        }

        if ($expectedSetData) {
            $this->builder
                ->expects($this->once())
                ->method('setData')
                ->with(self::CUSTOMER_ACCEPTANCE_DATA);
        } else {
            $this->builder
                ->expects($this->never())
                ->method('setData');
        }

        $this->formType->buildForm($this->builder, $options);
    }

    public function buildFormWithFeatureEnabledProvider()
    {
        $customerUser = $this->getEntity(CustomerUser::class, ['id' => 1]);

        return [
            'Option "customerUser" contains "false" value' => [
                'options' => [
                    'customerUser' => false
                ],
                'expectedContextWillBeInitializer' => false,
                'expectedSetData' => false
            ],
            'Option "customerUser" contains "null" value' => [
                'options' => [
                    'customerUser' => null
                ],
                'expectedContextWillBeInitializer' => true,
                'expectedSetData' => false
            ],
            'Option "customerUser" contains instance of CustomerUser' => [
                'options' => [
                    'customerUser' => $customerUser
                ],
                'expectedContextWillBeInitializer' => true,
                'expectedSetData' => true
            ],
        ];
    }
}
