<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\Form;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Genemu\Bundle\FormBundle\Form\JQuery\Type\Select2Type;
use Oro\Bundle\ConsentBundle\Entity\Consent;
use Oro\Bundle\ConsentBundle\Form\Type\ConsentSelectType;
use Oro\Bundle\ConsentBundle\Form\Type\ConsentSelectWithPriorityType;
use Oro\Bundle\ConsentBundle\SystemConfig\ConsentConfig;
use Oro\Bundle\CustomerBundle\Tests\Unit\Form\Type\Stub\EntityType;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\FormBundle\Autocomplete\SearchHandlerInterface;
use Oro\Bundle\FormBundle\Autocomplete\SearchRegistry;
use Oro\Bundle\FormBundle\Form\Extension\SortableExtension;
use Oro\Bundle\FormBundle\Form\Type\OroEntitySelectOrCreateInlineType;
use Oro\Bundle\FormBundle\Form\Type\OroJquerySelect2HiddenType;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Validation;

class ConsentSelectWithPriorityTypeTest extends FormIntegrationTestCase
{
    use EntityTrait;

    /** @var ConsentSelectWithPriorityType */
    private $formType;

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

        $this->formType = new ConsentSelectWithPriorityType();
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        $entityType = new EntityType([]);

        /** @var AuthorizationCheckerInterface|\PHPUnit_Framework_MockObject_MockObject $authorizationChecker */
        $authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);

        /** @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject $configManager */
        $configManager = $this->createMock(ConfigManager::class);

        /** @var EntityManager|\PHPUnit_Framework_MockObject_MockObject $entityManager */
        $entityManager = $this->createMock(EntityManager::class);

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

        $metadata = $this->createMock(ClassMetadata::class);
        $entityManager->expects($this->any())
            ->method('getClassMetadata')
            ->willReturn($metadata);
        $metadata->expects($this->any())
            ->method('getSingleIdentifierFieldName')
            ->willReturn('id');

        /** @var ConfigProvider|\PHPUnit_Framework_MockObject_MockObject $configProvider */
        $configProvider = $this->createMock(ConfigProvider::class);

        return [
            new PreloadedExtension(
                [
                    ConsentSelectWithPriorityType::NAME => new ConsentSelectWithPriorityType(),
                    $entityType->getName() => $entityType,
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
                ],
                [
                    'form' => [new SortableExtension()],
                ]
            ),
            new ValidatorExtension(Validation::createValidator()),
        ];
    }

    /**
     * @param array $submittedData
     * @param array $expectedData
     * @dataProvider submitDataProvider
     */
    public function testSubmit(array $submittedData, $expectedData)
    {
        $options = [
            'data_class' => ConsentConfig::class,
        ];
        $form = $this->factory->create(ConsentSelectWithPriorityType::NAME, new ConsentConfig(), $options);

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($expectedData, $form->getData());
    }

    /**
     * @return array
     */
    public function submitDataProvider()
    {
        $existingConsent = $this->getEntity(Consent::class, ['id' => 1]);
        $expectedConsent = $this->getEntity(Consent::class, ['id' => 2]);

        return [
            'without default data' => [
                'submittedData' => [
                    'consent' => 2,
                    '_position' => 100,
                ],
                'expectedData' => (new ConsentConfig())
                    ->setSortOrder(100)
                    ->setConsent($expectedConsent),
            ],
            'with default data' => [
                'submittedData' => [
                    'consent' => 2,
                    '_position' => 100,
                ],
                'expectedData' => (new ConsentConfig())
                    ->setSortOrder(100)
                    ->setConsent($expectedConsent),
            ],
        ];
    }

    public function testGetName()
    {
        $this->assertEquals(ConsentSelectWithPriorityType::NAME, $this->formType->getName());
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals(ConsentSelectWithPriorityType::NAME, $this->formType->getBlockPrefix());
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
