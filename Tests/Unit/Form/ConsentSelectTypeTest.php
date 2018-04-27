<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\Form;

use Oro\Bundle\ConsentBundle\Entity\Consent;
use Oro\Bundle\ConsentBundle\Form\Type\ConsentSelectType;
use Oro\Bundle\FormBundle\Form\Type\OroEntitySelectOrCreateInlineType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ConsentSelectTypeTest extends \PHPUnit_Framework_TestCase
{
    /** @var ConsentSelectType */
    private $formType;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->formType = new ConsentSelectType();
    }

    public function testSetDefaultOptions()
    {
        $resolver = $this->createMock(OptionsResolverInterface::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with($this->isType('array'))
            ->willReturnCallback(
                function (array $options) {
                    $this->assertArrayHasKey('autocomplete_alias', $options);
                    $this->assertEquals('oro_consent_list', $options['autocomplete_alias']);

                    $this->assertArrayHasKey('entity_class', $options);
                    $this->assertEquals(Consent::class, $options['entity_class']);

                    $this->assertArrayHasKey('grid_name', $options);
                    $this->assertEquals('consents-grid', $options['grid_name']);

                    $this->assertArrayHasKey('create_form_route', $options);
                    $this->assertEquals('oro_consent_create', $options['create_form_route']);

                    $this->assertArrayHasKey('configs', $options);
                    $this->assertEquals(
                        ['placeholder' => 'oro.consent.form.choose_consent'],
                        $options['configs']
                    );
                }
            );

        $this->formType->setDefaultOptions($resolver);
    }

    public function testGetParent()
    {
        $this->assertEquals(OroEntitySelectOrCreateInlineType::NAME, $this->formType->getParent());
    }

    public function testGetName()
    {
        $this->assertEquals(ConsentSelectType::NAME, $this->formType->getName());
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals(ConsentSelectType::NAME, $this->formType->getBlockPrefix());
    }
}
