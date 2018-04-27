<?php

namespace Oro\Bundle\ConsentBundle\Condition;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\ConsentBundle\Provider\ConsentDataProvider;
use Oro\Component\ConfigExpression\Condition\AbstractCondition;
use Oro\Component\ConfigExpression\ContextAccessorAwareInterface;
use Oro\Component\ConfigExpression\ContextAccessorAwareTrait;
use Oro\Component\ConfigExpression\Exception\InvalidArgumentException;
use Symfony\Component\PropertyAccess\PropertyPath;

/**
 * Workflow condition that check that customer user has unaccepted consents
 */
class CheckoutHasUnacceptedConsents extends AbstractCondition implements ContextAccessorAwareInterface
{
    use ContextAccessorAwareTrait;

    const NAME = 'checkout_has_no_unaccepted_consents';

    /**
     * @var PropertyPath
     */
    private $checkout;

    /**
     * @var ConsentDataProvider
     */
    private $consentDataProvider;

    /**
     * @param ConsentDataProvider $consentDataProvider
     */
    public function __construct(ConsentDataProvider $consentDataProvider)
    {
        $this->consentDataProvider = $consentDataProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        if (array_key_exists('checkout', $options)) {
            $this->checkout = $options['checkout'];
        } elseif (array_key_exists(0, $options)) {
            $this->checkout = $options[0];
        }

        if (!$this->checkout) {
            throw new InvalidArgumentException('Missing "checkout" option');
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    protected function isConditionAllowed($context)
    {
        /** @var Checkout $checkout */
        $checkout = $this->resolveValue($context, $this->checkout, false);

        if (!$checkout instanceof Checkout) {
            return false;
        }

        $customerUser = $checkout->getCustomerUser();

        // In case of guest user checkout we can't get not accepted consents, so always return true
        if (!$customerUser) {
            return true;
        }

        return !empty(
            $this->consentDataProvider->getNotAcceptedRequiredConsentData($customerUser)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        return $this->convertToArray([$this->checkout]);
    }

    /**
     * {@inheritdoc}
     */
    public function compile($factoryAccessor)
    {
        return $this->convertToPhpCode([$this->checkout], $factoryAccessor);
    }
}
