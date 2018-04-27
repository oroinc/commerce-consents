<?php

namespace Oro\Bundle\ConsentBundle\Form\Type;

use Oro\Bundle\ConsentBundle\SystemConfig\ConsentConfig;
use Oro\Bundle\ConsentBundle\Validator\Constraints\UniqueConsent;
use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for the collection of Consent select with ordering types
 */
class ConsentCollectionType extends AbstractType
{
    const NAME = 'oro_consent_collection';

    /** @var DataTransformerInterface */
    protected $consentCollectionTransformer;

    /**
     ** @param DataTransformerInterface $consentCollectionTransformer
     */
    public function __construct(DataTransformerInterface $consentCollectionTransformer)
    {
        $this->consentCollectionTransformer = $consentCollectionTransformer;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addModelTransformer($this->consentCollectionTransformer);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'type' => ConsentSelectWithPriorityType::NAME,
                'options' => [
                    'data_class' => ConsentConfig::class,
                ],
                'constraints' => [new UniqueConsent()],
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return CollectionType::NAME;
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
    public function getBlockPrefix()
    {
        return self::NAME;
    }
}
