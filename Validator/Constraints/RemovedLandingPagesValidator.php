<?php

namespace Oro\Bundle\ConsentBundle\Validator\Constraints;

use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\CMSBundle\Entity\Repository\PageRepository;
use Oro\Bundle\ConsentBundle\Entity\ConsentAcceptance;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates that all landing pages that uses in checked consents exist in the database
 */
class RemovedLandingPagesValidator extends ConstraintValidator
{
    /**
     * @var DoctrineHelper
     */
    private $doctrineHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!is_array($value)) {
            throw new \LogicException("Incorrect type of the value!");
        }

        $checkedLandingPageIds = array_filter(
            array_map(
                function (ConsentAcceptance $consentAcceptance) {
                    return $consentAcceptance->getLandingPage() ? $consentAcceptance->getLandingPage()->getId() : null;
                },
                $value
            )
        );

        if (empty($checkedLandingPageIds)) {
            return;
        }

        $nonExistentPageIds = $this->getNonExistentPageIds($checkedLandingPageIds);
        if (!empty($nonExistentPageIds)) {
            $this->context
                ->buildViolation($constraint->message)
                ->addViolation();
        }
    }

    /**
     * @param array $pageIds
     *
     * @return array
     */
    private function getNonExistentPageIds(array $pageIds)
    {
        /** @var PageRepository $pageRepository */
        $pageRepository = $this->doctrineHelper->getEntityRepository(Page::class);
        $qb = $pageRepository->createQueryBuilder('p');
        $qb
            ->select('p.id')
            ->where($qb->expr()->in('p.id', ':landingPageIds'));

        $qb->setParameter('landingPageIds', $pageIds);

        $result = $qb->getQuery()->getArrayResult();

        $existingPageIds = array_column($result, 'id');

        return array_diff($pageIds, $existingPageIds);
    }
}
