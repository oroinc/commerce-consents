<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\ConsentBundle\Form\EventSubscriber\CustomerConsentsEventSubscriber;
use Oro\Bundle\ConsentBundle\Form\EventSubscriber\FillConsentContextEventSubscriber;
use Oro\Bundle\ConsentBundle\Form\EventSubscriber\GuestCustomerConsentsEventSubscriber;
use Oro\Bundle\ConsentBundle\Form\EventSubscriber\PopulateFieldCustomerConsentsSubscriber;
use Oro\Bundle\ConsentBundle\Form\Extension\FrontendRfqExtension;
use Oro\Bundle\ConsentBundle\Form\Type\CustomerConsentsType;
use Oro\Bundle\ConsentBundle\Helper\ConsentContextInitializeHelperInterface;
use Oro\Bundle\ConsentBundle\Validator\Constraints\RequiredConsents;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class FrontendRfqExtensionTest extends FormIntegrationTestCase
{
    use EntityTrait;

    /** @var FrontendRfqExtension */
    private $extension;

    /** @var FormBuilderInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $builder;

    /** @var FeatureChecker|\PHPUnit_Framework_MockObject_MockObject */
    private $featureChecker;

    /** @var CustomerConsentsEventSubscriber|\PHPUnit_Framework_MockObject_MockObject */
    private $saveConsentAcceptanceSubscriber;

    /** @var FillConsentContextEventSubscriber|\PHPUnit_Framework_MockObject_MockObject */
    private $fillConsentContextEventSubscriber;

    /** @var GuestCustomerConsentsEventSubscriber|\PHPUnit_Framework_MockObject_MockObject */
    private $guestCustomerConsentsEventSubscriber;

    /** @var PopulateFieldCustomerConsentsSubscriber|\PHPUnit_Framework_MockObject_MockObject */
    private $populateFieldCustomerConsentsSubscriber;

    /** @var ConsentContextInitializeHelperInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $consentContextInitializeHelper;

    /** @var TokenStorageInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $tokenStorage;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $this->saveConsentAcceptanceSubscriber = $this->createMock(CustomerConsentsEventSubscriber::class);
        $this->fillConsentContextEventSubscriber = $this->createMock(FillConsentContextEventSubscriber::class);
        $this->guestCustomerConsentsEventSubscriber = $this->createMock(GuestCustomerConsentsEventSubscriber::class);
        $this->consentContextInitializeHelper = $this->createMock(ConsentContextInitializeHelperInterface::class);
        $this->populateFieldCustomerConsentsSubscriber = $this->createMock(
            PopulateFieldCustomerConsentsSubscriber::class
        );
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);

        $this->extension = new FrontendRfqExtension(
            $this->saveConsentAcceptanceSubscriber,
            $this->fillConsentContextEventSubscriber,
            $this->guestCustomerConsentsEventSubscriber,
            $this->populateFieldCustomerConsentsSubscriber,
            $this->consentContextInitializeHelper,
            $this->tokenStorage
        );

        $this->extension->setFeatureChecker($this->featureChecker);
        $this->extension->addFeature('consents');

        $this->builder = $this->createMock(FormBuilderInterface::class);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->featureChecker);
        unset($this->saveConsentAcceptanceSubscriber);
        unset($this->fillConsentContextEventSubscriber);
        unset($this->extension);
        unset($this->builder);
        unset($this->guestCustomerConsentsEventSubscriber);
        unset($this->consentContextInitializeHelper);
        unset($this->tokenStorage);
    }

    public function testBuildFormWithFeatureDisabled()
    {
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('consents')
            ->willReturn(false);

        $this->builder->expects($this->never())
            ->method('addEventSubscriber');
        $this->builder->expects($this->never())
            ->method('add');

        $this->extension->buildForm($this->builder, []);
    }

    public function testBuildFormWithFeatureEnabledAndLoggedCustomerUser()
    {
        $token = $this->createMock(TokenInterface::class);
        $token
            ->method('getUser')
            ->willReturn($this->getEntity(CustomerUser::class, ['id' => 1]));

        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('consents')
            ->willReturn(true);

        $this->builder->expects($this->exactly(3))
            ->method('addEventSubscriber')
            ->withConsecutive(
                [$this->saveConsentAcceptanceSubscriber],
                [$this->fillConsentContextEventSubscriber],
                [$this->populateFieldCustomerConsentsSubscriber]
            );

        $this->builder->expects($this->once())
            ->method('add')
            ->with(
                CustomerConsentsType::TARGET_FIELDNAME,
                CustomerConsentsType::class,
                [
                    'constraints' => [new RequiredConsents()]
                ]
            );

        $this->extension->buildForm($this->builder, []);
    }

    public function testBuildFormWithFeatureEnabledAndAnonymousCustomerUser()
    {
        $token = $this->createMock(AnonymousCustomerUserToken::class);

        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('consents')
            ->willReturn(true);

        $this->builder->expects($this->once())
            ->method('addEventSubscriber')
            ->with($this->guestCustomerConsentsEventSubscriber);

        $this->consentContextInitializeHelper
            ->expects($this->once())
            ->method('initialize');

        $this->builder->expects($this->once())
            ->method('add')
            ->with(
                CustomerConsentsType::TARGET_FIELDNAME,
                CustomerConsentsType::class,
                [
                    'constraints' => [new RequiredConsents()]
                ]
            );

        $this->extension->buildForm($this->builder, []);
    }

    public function testGetExtendedType()
    {
        $this->assertNull($this->extension->getExtendedType());

        $this->extension->setExtendedType('extended_type');
        $this->assertEquals('extended_type', $this->extension->getExtendedType());
    }
}
