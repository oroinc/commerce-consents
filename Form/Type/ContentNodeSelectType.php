<?php

namespace Oro\Bundle\ConsentBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\EntityTreeSelectType;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Bundle\WebCatalogBundle\JsTree\ContentNodeTreeHandler;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContentNodeSelectType extends AbstractType
{
    const NAME = 'oro_consent_web_catalog_content_node_select';
    const EMPTY_WEB_CATALOG = 'oro_consent_empty_web_catalog';

    /**
     * @var ContentNodeTreeHandler
     */
    private $treeHandler;

    /**
     * @param ContentNodeTreeHandler $treeHandler
     */
    public function __construct(ContentNodeTreeHandler $treeHandler)
    {
        $this->treeHandler = $treeHandler;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefined(['web_catalog']);
        $resolver->setAllowedTypes('web_catalog', [WebCatalog::class]);
        $resolver->setDefaults([
            'class' => ContentNode::class,
            'tree_key' => 'consent-content-node',
            'tree_data' => [],
            'auto_initialize' => false,
            'page_component_module' => 'oroconsent/js/app/views/consent-entity-tree-select-form'
        ]);

        $resolver->setNormalizer(
            'tree_data',
            function (Options $options) {
                $webCatalog = $options['web_catalog'] ?? null;
                /**
                 * @todo Should refactor in BB-13929
                 */
                if (null === $webCatalog) {
                    return self::EMPTY_WEB_CATALOG;
                }

                return function () use ($webCatalog) {
                    $treeRoot = $this->treeHandler->getTreeRootByWebCatalog($webCatalog);
                    /**
                     * @todo Should refactor in BB-13929
                     */
                    if (null === $webCatalog) {
                        return self::EMPTY_WEB_CATALOG;
                    }

                    return $this->treeHandler->createTree($treeRoot, true);
                };
            }
        );
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

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return EntityTreeSelectType::class;
    }
}
