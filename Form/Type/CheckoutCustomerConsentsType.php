<?php

namespace Oro\Bundle\ConsentBundle\Form\Type;

use Oro\Bundle\ConsentBundle\Helper\ConsentContextInitializeHelperInterface;
use Oro\Bundle\ConsentBundle\Provider\ConsentAcceptanceProvider;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureToggleableInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * The field that is used for managing customer user accepted consents on the checkout page
 */
class CheckoutCustomerConsentsType extends AbstractType implements FeatureToggleableInterface
{
    use FeatureCheckerHolderTrait;

    const NAME = 'oro_checkout_customer_consents';
    const CUSTOMER_USER_OPTION_NAME = 'customerUser';

    /** @var ConsentContextInitializeHelperInterface */
    private $contextInitializeHelper;

    /** @var ConsentAcceptanceProvider */
    private $consentAcceptanceProvider;

    /**
     * @param ConsentContextInitializeHelperInterface $contextInitializeHelper
     * @param ConsentAcceptanceProvider               $consentAcceptanceProvider
     */
    public function __construct(
        ConsentContextInitializeHelperInterface $contextInitializeHelper,
        ConsentAcceptanceProvider $consentAcceptanceProvider
    ) {
        $this->contextInitializeHelper = $contextInitializeHelper;
        $this->consentAcceptanceProvider = $consentAcceptanceProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (!$this->isFeaturesEnabled()) {
            return;
        }

        $customerUser = $options[self::CUSTOMER_USER_OPTION_NAME];
        if (false !== $customerUser) {
            $this->contextInitializeHelper->initialize($customerUser);
            if ($customerUser instanceof CustomerUser) {
                $consentAcceptances = $this->consentAcceptanceProvider->getCustomerConsentAcceptances();
                $builder->setData($consentAcceptances);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                self::CUSTOMER_USER_OPTION_NAME => false,
            ]
        );

        $resolver->addAllowedTypes(
            self::CUSTOMER_USER_OPTION_NAME,
            [
                CustomerUser::class,
                'null',
                'bool', // we use bool to solve issue with calling buildForm with invalid options
            ]
        );

        $resolver->setDefined(self::CUSTOMER_USER_OPTION_NAME);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return CustomerConsentsType::class;
    }
}
