<?php

namespace Oro\Bundle\ConsentBundle\Tests\Functional\Validator;

use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\ConsentBundle\Entity\ConsentAcceptance;
use Oro\Bundle\ConsentBundle\Tests\Functional\DataFixtures\LoadPageDataWithSlug;
use Oro\Bundle\ConsentBundle\Validator\Constraints\RemovedLandingPages;
use Oro\Bundle\ConsentBundle\Validator\Constraints\RemovedLandingPagesValidator;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Validator\Context\ExecutionContext;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class RemovedLandingPagesValidatorTest extends WebTestCase
{
    use EntityTrait;

    /** @var RemovedLandingPagesValidator */
    private $validator;

    /** @var RemovedLandingPages */
    private $constraint;

    /** @var ExecutionContext|\PHPUnit_Framework_MockObject_MockObject $context */
    private $context;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures(
            [
                LoadPageDataWithSlug::class,
            ]
        );

        $this->constraint = new RemovedLandingPages();
        $this->validator = $this->client->getContainer()->get('oro_consent.validator.removed_landing_pages');
        $this->context = $this->createMock(ExecutionContext::class);
        $this->validator->initialize($this->context);
    }

    /**
     * @dataProvider validateProvider
     *
     * @param callable $getValueCallback
     * @param bool     $isValid
     */
    public function testValidate(callable $getValueCallback, $isValid)
    {
        if ($isValid) {
            $this->context
                ->expects($this->never())
                ->method('buildViolation');
        } else {
            $this->context
                ->expects($this->once())
                ->method('buildViolation')
                ->with($this->constraint->message)
                ->willReturn(
                    $this->createMock(ConstraintViolationBuilderInterface::class)
                );
        }

        $value = $getValueCallback();

        $this->validator->validate($value, $this->constraint);
    }

    /**
     * @return array
     */
    public function validateProvider()
    {
        return [
            "Empty value" => [
                'getValueCallback' => function () {
                    return [];
                },
                'isValid' => true,
            ],
            "Only existed landing in value" => [
                'getValueCallback' => function () {
                    $consentAcceptanceWithExistedLandingPage = $this->getEntity(
                        \Oro\Bundle\ConsentBundle\Entity\ConsentAcceptance::class,
                        [
                            'id' => 7,
                            'landingPage' => $this->getEntity(
                                Page::class,
                                [
                                    'id' => $this->getReference(LoadPageDataWithSlug::PAGE_1)->getId(),
                                ]
                            ),
                        ]
                    );

                    return [$consentAcceptanceWithExistedLandingPage];
                },
                'isValid' => true,
            ],
            "Only not existed landing in value" => [
                'getValueCallback' => function () {
                    $consentAcceptanceWithNonExistentLandingPage = $this->getEntity(
                        ConsentAcceptance::class,
                        [
                            'id' => 8,
                            'landingPage' => $this->getEntity(Page::class, ['id' => PHP_INT_MAX]),
                        ]
                    );

                    return [$consentAcceptanceWithNonExistentLandingPage];
                },
                'isValid' => false,
            ],
            "Consent acceptance without landing page" => [
                'getValueCallback' => function () {
                    $consentAcceptanceWithEmptyLandingPage = $this->getEntity(
                        ConsentAcceptance::class,
                        [
                            'id' => 9,
                        ]
                    );

                    return [$consentAcceptanceWithEmptyLandingPage];
                },
                'isValid' => true,
            ],
        ];
    }
}
