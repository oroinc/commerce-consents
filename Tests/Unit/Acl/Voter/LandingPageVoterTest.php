<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\Acl\Voter;

use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\ConsentBundle\Acl\Voter\LandingPageVoter;
use Oro\Bundle\ConsentBundle\Entity\Repository\ConsentAcceptanceRepository;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class LandingPageVoterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var LandingPageVoter
     */
    protected $voter;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->voter = new LandingPageVoter($this->doctrineHelper);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->voter);
        unset($this->doctrineHelper);
    }

    /**
     * @dataProvider supportsAttributeDataProvider
     *
     * @param string $attribute
     * @param bool $expected
     */
    public function testSupportsAttribute($attribute, $expected)
    {
        $this->assertEquals($expected, $this->voter->supportsAttribute($attribute));
    }

    /**
     * @return array
     */
    public function supportsAttributeDataProvider()
    {
        return [
            'VIEW' => ['VIEW', false],
            'CREATE' => ['CREATE', false],
            'EDIT' => ['EDIT', true],
            'DELETE' => ['DELETE', true],
            'ASSIGN' => ['ASSIGN', false],
        ];
    }

    /**
     * @dataProvider supportsClassDataProvider
     *
     * @param string $class
     * @param string $actualClass
     * @param bool $expected
     */
    public function testSupportsClass($class, $actualClass, $expected)
    {
        $this->voter->setClassName($actualClass);

        $this->assertEquals($expected, $this->voter->supportsClass($class));
    }

    /**
     * @return array
     */
    public function supportsClassDataProvider()
    {
        return [
            'supported class' => [Page::class, Page::class, true],
            'not supported class' => ['NotSupportedClass', Page::class, false],
        ];
    }

    /**
     * @dataProvider attributesDataProvider
     *
     * @param string $attribute
     * @param $hasConsents
     * @param $expected
     */
    public function testVote($attribute, $hasConsents, $expected)
    {
        $object = $this->createMock(Page::class);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityClass')
            ->with($object)
            ->will($this->returnValue(Page::class));

        $this->voter->setClassName(Page::class);

        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->with($object, false)
            ->will($this->returnValue(1));

        if ($this->voter->supportsAttribute($attribute)) {
            $this->assertHasConsents($hasConsents);
        }

        /** @var TokenInterface $token */
        $token = $this->createMock(TokenInterface::class);
        $this->assertEquals(
            $expected,
            $this->voter->vote($token, $object, [$attribute])
        );
    }

    /**
     * @return array
     */
    public function attributesDataProvider()
    {
        return [
            ['VIEW', false, LandingPageVoter::ACCESS_ABSTAIN],
            ['CREATE', false, LandingPageVoter::ACCESS_ABSTAIN],
            ['EDIT', true, LandingPageVoter::ACCESS_DENIED],
            ['EDIT', false, LandingPageVoter::ACCESS_ABSTAIN],
            ['DELETE', true, LandingPageVoter::ACCESS_DENIED],
            ['DELETE', false, LandingPageVoter::ACCESS_ABSTAIN],
            ['ASSIGN', false, LandingPageVoter::ACCESS_ABSTAIN],
        ];
    }

    /**
     * @param $assertHasConsents
     */
    protected function assertHasConsents($assertHasConsents)
    {
        $landingPage = $this->createMock(Page::class);
        $repository = $this->createMock(ConsentAcceptanceRepository::class);

        $this->doctrineHelper
            ->expects($this->once())
            ->method('getEntityReference')
            ->with(Page::class, 1)
            ->willReturn($landingPage);

        $repository
            ->expects($this->once())
            ->method('hasLandingPageAcceptedConsents')
            ->will($this->returnValue($assertHasConsents));

        $this->doctrineHelper
            ->expects($this->once())
            ->method('getEntityRepository')
            ->will($this->returnValue($repository));
    }
}
