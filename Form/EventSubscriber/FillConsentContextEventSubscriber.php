<?php

namespace Oro\Bundle\ConsentBundle\Form\EventSubscriber;

use Oro\Bundle\ConsentBundle\Extractor\CustomerUserExtractor;
use Oro\Bundle\ConsentBundle\Form\Type\CustomerConsentsType;
use Oro\Bundle\ConsentBundle\Helper\ConsentContextInitializeHelperInterface;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Listener that fills consent context, main data-source that is used by all submodules
 */
class FillConsentContextEventSubscriber implements EventSubscriberInterface
{
    /** @var ConsentContextInitializeHelperInterface */
    private $contextInitializeHelper;

    /** @var CustomerUserExtractor */
    private $customerUserExtractor;

    /**
     * @param ConsentContextInitializeHelperInterface $contextInitializeHelper
     * @param CustomerUserExtractor $customerUserExtractor
     */
    public function __construct(
        ConsentContextInitializeHelperInterface $contextInitializeHelper,
        CustomerUserExtractor $customerUserExtractor
    ) {
        $this->contextInitializeHelper = $contextInitializeHelper;
        $this->customerUserExtractor = $customerUserExtractor;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::POST_SET_DATA => ['fillConsentContext', 1000]
        ];
    }

    /**
     * @param FormEvent $event
     */
    public function fillConsentContext(FormEvent $event)
    {
        $customerUser = $this->getCustomerUserByEvent($event);
        if ($customerUser instanceof CustomerUser &&
            $event->getForm()->has(CustomerConsentsType::TARGET_FIELDNAME)
        ) {
            $this->contextInitializeHelper->initialize($customerUser);
        }
    }

    /**
     * @param FormEvent $event
     *
     * @return null|CustomerUser
     */
    private function getCustomerUserByEvent(FormEvent $event)
    {
        $customerUser = $event->getData();
        if ($customerUser instanceof CustomerUser) {
            return $customerUser;
        }

        return $this->customerUserExtractor->extract($customerUser);
    }
}
