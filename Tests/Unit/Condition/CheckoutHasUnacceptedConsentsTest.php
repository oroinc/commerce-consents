<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\Condition;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Tests\Unit\Condition\ToStringStub;
use Oro\Bundle\ConsentBundle\Condition\CheckoutHasUnacceptedConsents;
use Oro\Bundle\ConsentBundle\Entity\Consent;
use Oro\Bundle\ConsentBundle\Provider\ConsentDataProvider;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Component\ConfigExpression\Exception\InvalidArgumentException;
use Oro\Component\Testing\Unit\EntityTrait;

class CheckoutHasUnacceptedConsentsTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var CheckoutHasUnacceptedConsents */
    protected $condition;

    /** @var ConsentDataProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $consentDataProvider;

    protected function setUp()
    {
        $this->consentDataProvider = $this->createMock(ConsentDataProvider::class);

        $this->condition = new CheckoutHasUnacceptedConsents($this->consentDataProvider);
    }

    public function testGetName()
    {
        $this->assertEquals(CheckoutHasUnacceptedConsents::NAME, $this->condition->getName());
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Missing "checkout" option
     */
    public function testInitializeInvalid()
    {
        $this->assertInstanceOf(
            'Oro\Component\ConfigExpression\Condition\AbstractCondition',
            $this->condition->initialize([])
        );
    }

    public function testInitialize()
    {
        $this->assertInstanceOf(
            'Oro\Component\ConfigExpression\Condition\AbstractCondition',
            $this->condition->initialize(['checkout' => new \stdClass()])
        );
    }

    /**
     * @dataProvider evaluateProvider
     *
     * @param array $consents
     * @param bool $expected
     */
    public function testEvaluate($consents, $expected)
    {
        $checkout = $this->createMock(Checkout::class);
        $customerUser = $this->createMock(CustomerUser::class);
        $checkout->expects($this->once())
            ->method('getCustomerUser')
            ->willReturn($customerUser);
        $this->consentDataProvider->expects($this->once())
            ->method('getNotAcceptedRequiredConsentData')
            ->with($customerUser)
            ->willReturn($consents);

        $this->condition->initialize(['checkout' => $checkout]);
        $this->assertEquals($expected, $this->condition->evaluate([]));
    }

    /**
     * @return array
     */
    public function evaluateProvider()
    {
        return [
            'no_unaccepted_consents' => [
                'consents' => [],
                'expected' => false,
            ],
            'has_unaccepted_consents' => [
                'consents' => [new Consent(), new Consent()],
                'expected' => true,
            ]
        ];
    }

    public function testToArray()
    {
        $stdClass = new \stdClass();
        $this->condition->initialize(['checkout' => $stdClass]);
        $result = $this->condition->toArray();

        $key = '@'.CheckoutHasUnacceptedConsents::NAME;

        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey($key, $result);

        $resultSection = $result[$key];
        $this->assertInternalType('array', $resultSection);
        $this->assertArrayHasKey('parameters', $resultSection);
        $this->assertContains($stdClass, $resultSection['parameters']);
    }

    public function testCompile()
    {
        $toStringStub = new ToStringStub();
        $options = ['checkout' => $toStringStub];

        $this->condition->initialize($options);
        $result = $this->condition->compile('$factory');
        $this->assertEquals(
            sprintf(
                '$factory->create(\'%s\', [%s])',
                CheckoutHasUnacceptedConsents::NAME,
                $toStringStub
            ),
            $result
        );
    }
}
