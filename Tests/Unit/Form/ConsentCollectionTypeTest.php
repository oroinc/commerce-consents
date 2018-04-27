<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\Form;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Genemu\Bundle\FormBundle\Form\JQuery\Type\Select2Type;
use Oro\Bundle\ConsentBundle\Entity\Consent;
use Oro\Bundle\ConsentBundle\Form\DataTransformer\ConsentCollectionTransformer;
use Oro\Bundle\ConsentBundle\Form\Type\ConsentCollectionType;
use Oro\Bundle\ConsentBundle\Form\Type\ConsentSelectType;
use Oro\Bundle\ConsentBundle\Form\Type\ConsentSelectWithPriorityType;
use Oro\Bundle\ConsentBundle\SystemConfig\ConsentConfigConverter;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\FormBundle\Autocomplete\SearchHandlerInterface;
use Oro\Bundle\FormBundle\Autocomplete\SearchRegistry;
use Oro\Bundle\FormBundle\Form\Extension\SortableExtension;
use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Oro\Bundle\FormBundle\Form\Type\OroEntitySelectOrCreateInlineType;
use Oro\Bundle\FormBundle\Form\Type\OroJquerySelect2HiddenType;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Validation;

class ConsentCollectionTypeTest extends FormIntegrationTestCase
{
    use EntityTrait;

    /** @var ConsentCollectionType */
    private $formType;

    /** @var ConsentCollectionTransformer */
    private $collectionTransformer;

    /** @var SearchRegistry|\PHPUnit_Framework_MockObject_MockObject $authorizationChecker */
    private $searchRegistry;

    /** @var SearchHandlerInterface|\PHPUnit_Framework_MockObject_MockObject $authorizationChecker */
    private $searchHandler;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();

        $this->formType = new ConsentCollectionType($this->collectionTransformer);
    }

    /**
     * @param array $submitted
     * @param array $expected
     * @dataProvider submitDataProvider
     */
    public function testSubmit(array $submitted, array $expected)
    {
        $form = $this->factory->create($this->formType);
        $form->submit($submitted);

        $this->assertTrue($form->isValid());
        $this->assertEquals($expected, $form->getData());
    }

    /**
     * @return array
     */
    public function submitDataProvider()
    {
        return [
            [
                'submitted' => [
                    [
                        ConsentConfigConverter::CONSENT_KEY => '1',
                        '_position' => '6',
                    ],
                    [
                        ConsentConfigConverter::CONSENT_KEY => '3',
                        '_position' => '7',
                    ],
                    [
                        ConsentConfigConverter::CONSENT_KEY => '2',
                        '_position' => '5',
                    ],
                ],
                'expected' => [
                    [
                        ConsentConfigConverter::CONSENT_KEY => 1,
                        ConsentConfigConverter::SORT_ORDER_KEY => 6
                    ],
                    [
                        ConsentConfigConverter::CONSENT_KEY => 3,
                        ConsentConfigConverter::SORT_ORDER_KEY => 7
                    ],
                    [
                        ConsentConfigConverter::CONSENT_KEY => 2,
                        ConsentConfigConverter::SORT_ORDER_KEY => 5
                    ],
                ]
            ]
        ];
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        $entityType = new EntityType([]);

        $doctrine = $this->createMock(RegistryInterface::class);

        $consentConfigConverter = new ConsentConfigConverter($doctrine);
        $this->collectionTransformer = new ConsentCollectionTransformer($consentConfigConverter);

        /** @var AuthorizationCheckerInterface|\PHPUnit_Framework_MockObject_MockObject $authorizationChecker */
        $authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);

        /** @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject $configManager */
        $configManager = $this->createMock(ConfigManager::class);

        /** @var EntityManager|\PHPUnit_Framework_MockObject_MockObject $entityManager */
        $entityManager = $this->createMock(EntityManager::class);
        $metadata = $this->createMock(ClassMetadata::class);
        $entityManager->expects($this->any())
            ->method('getClassMetadata')
            ->willReturn($metadata);
        $metadata->expects($this->any())
            ->method('getSingleIdentifierFieldName')
            ->willReturn('id');

        /** @var EntityRepository|\PHPUnit_Framework_MockObject_MockObject $repository */
        $repository = $this->createMock(EntityRepository::class);
        $repository->expects($this->any())
            ->method('find')
            ->willReturnCallback(function ($id) {
                return $this->getEntity(Consent::class, ['id' => $id]);
            });
        $entityManager->expects($this->any())
            ->method('getRepository')
            ->with(Consent::class)
            ->willReturn($repository);

        /** @var ConfigProvider|\PHPUnit_Framework_MockObject_MockObject $configProvider */
        $configProvider = $this->createMock(ConfigProvider::class);

        return [
            new PreloadedExtension(
                [
                    CollectionType::NAME => new CollectionType(),
                    ConsentSelectWithPriorityType::NAME => new ConsentSelectWithPriorityType(),
                    ConsentSelectType::NAME => new ConsentSelectType(),
                    OroEntitySelectOrCreateInlineType::NAME => new OroEntitySelectOrCreateInlineType(
                        $authorizationChecker,
                        $configManager,
                        $entityManager,
                        $this->getMockSearchRegistry()
                    ),
                    OroJquerySelect2HiddenType::NAME => new OroJquerySelect2HiddenType(
                        $entityManager,
                        $this->getMockSearchRegistry(),
                        $configProvider
                    ),
                    'genemu_jqueryselect2_hidden' => new Select2Type('hidden'),
                    $entityType->getName() => $entityType,
                ],
                [
                    'form' => [new SortableExtension()],
                ]
            ),
            new ValidatorExtension(Validation::createValidator())
        ];
    }

    public function testGetParent()
    {
        $this->assertEquals(CollectionType::NAME, $this->formType->getParent());
    }

    public function testGetName()
    {
        $this->assertEquals(ConsentCollectionType::NAME, $this->formType->getName());
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals(ConsentCollectionType::NAME, $this->formType->getBlockPrefix());
    }

    /**
     * @return SearchRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getMockSearchRegistry()
    {
        if (!$this->searchRegistry) {
            $this->searchRegistry = $this->createMock(SearchRegistry::class);
            $this->searchRegistry->method('getSearchHandler')->willReturn($this->getMockSearchHandler());
        }

        return $this->searchRegistry;
    }

    /**
     * @return SearchHandlerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getMockSearchHandler()
    {
        if (!$this->searchHandler) {
            $this->searchHandler = $this->createMock(SearchHandlerInterface::class);
            $this->searchHandler->method('getProperties')->willReturn(['code', 'label']);
        }

        return $this->searchHandler;
    }
}
