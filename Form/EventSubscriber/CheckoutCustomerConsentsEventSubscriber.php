<?php

namespace Oro\Bundle\ConsentBundle\Form\EventSubscriber;

use Oro\Bundle\ConsentBundle\Entity\ConsentAcceptance;
use Oro\Bundle\ConsentBundle\Form\Type\CheckoutCustomerConsentsType;
use Oro\Bundle\ConsentBundle\Form\Type\CustomerConsentsType;
use Oro\Bundle\ConsentBundle\Handler\SaveConsentAcceptanceHandler;
use Oro\Bundle\ConsentBundle\Storage\CustomerConsentAcceptancesStorageInterface;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Process changes in consents after the main form was submitted.
 * We use saving logic here, because consents form isn't mapped by main form and
 * consents have no direct relation on customerUser entity.
 */
class CheckoutCustomerConsentsEventSubscriber implements EventSubscriberInterface
{
    /** @var SaveConsentAcceptanceHandler */
    private $saveConsentAcceptanceHandler;

    /** @var CustomerConsentAcceptancesStorageInterface */
    private $storage;

    /** @var TokenStorageInterface */
    private $tokenStorage;

    /**
     * @param SaveConsentAcceptanceHandler $saveConsentAcceptanceHandler
     * @param CustomerConsentAcceptancesStorageInterface $storage
     * @param TokenStorageInterface                      $tokenStorage
     */
    public function __construct(
        SaveConsentAcceptanceHandler $saveConsentAcceptanceHandler,
        CustomerConsentAcceptancesStorageInterface $storage,
        TokenStorageInterface $tokenStorage
    ) {
        $this->saveConsentAcceptanceHandler = $saveConsentAcceptanceHandler;
        $this->storage = $storage;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        /**
         * Add event listener after validation listener
         */
        return [FormEvents::POST_SUBMIT => ['saveConsentAcceptances', -10]];
    }

    /**
     * @param FormEvent $event
     */
    public function saveConsentAcceptances(FormEvent $event)
    {
        if (!$event->getForm()->has(CustomerConsentsType::TARGET_FIELDNAME) || !$event->getForm()->isValid()) {
            return;
        }

        $customerConsentsField = $event->getForm()->get(CustomerConsentsType::TARGET_FIELDNAME);
        $customerUser = $customerConsentsField
            ->getConfig()
            ->getOption(CheckoutCustomerConsentsType::CUSTOMER_USER_OPTION_NAME);

        /** @var ConsentAcceptance[] $consentAcceptances */
        $consentAcceptances = $customerConsentsField->getData();
        if (!is_array($consentAcceptances) || !$consentAcceptances) {
            return;
        }

        if ($customerUser instanceof CustomerUser) {
            $this->saveConsentAcceptanceHandler->save($customerUser, $consentAcceptances);
            /**
             * Save consents to the storage after the workflow step "Agreements"
             * This event subscriber processes only the case when anonymous customer user proceed to the checkout.
             */
        } elseif ($this->isGuestCustomerUser()) {
            $this->storage->saveData($consentAcceptances);
        }
    }

    /**
     * @return bool
     */
    private function isGuestCustomerUser()
    {
        return $this->tokenStorage->getToken() instanceof AnonymousCustomerUserToken;
    }
}
