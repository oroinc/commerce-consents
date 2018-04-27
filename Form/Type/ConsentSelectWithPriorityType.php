<?php

namespace Oro\Bundle\ConsentBundle\Form\Type;

use Oro\Bundle\ConsentBundle\Entity\Consent;
use Oro\Bundle\ConsentBundle\SystemConfig\ConsentConfigConverter;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Form type for Consent select type with ordering
 */
class ConsentSelectWithPriorityType extends AbstractType
{
    const NAME = 'oro_consent_select_with_priority';

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                ConsentConfigConverter::CONSENT_KEY,
                ConsentSelectType::NAME,
                [
                    'empty_data' => null,
                    'required' => true,
                    'label' => 'oro.consent.entity_label',
                    'create_enabled' => false,
                    'constraints' => [new NotBlank()],
                ]
            );
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'sortable' => true,
            'sortable_property_path' => ConsentConfigConverter::SORT_ORDER_KEY,
            'data_class' => Consent::class
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::NAME;
    }
}
