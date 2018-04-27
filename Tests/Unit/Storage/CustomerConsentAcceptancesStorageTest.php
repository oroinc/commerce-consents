<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\Storage;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\ConsentBundle\Entity\Consent;
use Oro\Bundle\ConsentBundle\Entity\ConsentAcceptance;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Oro\Bundle\ConsentBundle\Storage\SessionCustomerConsentAcceptancesStorage;

class CustomerConsentAcceptancesStorageTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject */
    private $doctrineHelper;

    /** @var SessionInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $session;

    /** @var SessionCustomerConsentAcceptancesStorage */
    private $storage;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->session = $this->createMock(SessionInterface::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->storage = new SessionCustomerConsentAcceptancesStorage();
        $this->storage->setDoctrineHelper($this->doctrineHelper);
        $this->storage->setStorage($this->session);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->session);
        unset($this->doctrineHelper);
        unset($this->storage);
    }

    public function testSaveData()
    {
        $consentAcceptanceWithCMSPage = $this->getEntity(ConsentAcceptance::class, [
            'landingPage' => $this->getEntity(Page::class, ['id' => 1]),
            'consent' => $this->getEntity(Consent::class, ['id' => 1]),
        ]);

        $consentAcceptanceWithoutCMSPage = $this->getEntity(ConsentAcceptance::class, [
            'consent' => $this->getEntity(Consent::class, ['id' => 2]),
        ]);

        $this->session
            ->expects($this->once())
            ->method('set')
            ->with(
                'guest_customer_consents_accepted',
                '[{"consentId":1,"cmsPageId":1},{"consentId":2,"cmsPageId":null}]'
            );

        $this->storage->saveData([$consentAcceptanceWithCMSPage, $consentAcceptanceWithoutCMSPage]);
    }

    public function testGetData()
    {
        $consentAcceptanceWithCMSPage = $this->getEntity(ConsentAcceptance::class, [
            'landingPage' => $this->getEntity(Page::class, ['id' => 1]),
            'consent' => $this->getEntity(Consent::class, ['id' => 1]),
        ]);

        $consentAcceptanceWithoutCMSPage = $this->getEntity(ConsentAcceptance::class, [
            'consent' => $this->getEntity(Consent::class, ['id' => 2]),
        ]);

        $this->doctrineHelper
            ->expects($this->any())
            ->method('createEntityInstance')
            ->willReturnCallback(function ($className) {
                return $this->getEntity($className);
            });

        $this->doctrineHelper
            ->expects($this->any())
            ->method('getEntityReference')
            ->willReturnCallback(function ($className, $id) {
                return $this->getEntity($className, ['id' => $id]);
            });

        $this->session
            ->expects($this->once())
            ->method('get')
            ->with('guest_customer_consents_accepted')
            ->willReturn('[{"consentId":1,"cmsPageId":1},{"consentId":2,"cmsPageId":null}]');

        $this->assertEquals(
            [$consentAcceptanceWithCMSPage, $consentAcceptanceWithoutCMSPage],
            $this->storage->getData()
        );
    }
}
