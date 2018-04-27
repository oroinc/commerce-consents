<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\ConsentBundle\Validator\Constraints\UniqueConsent;
use Symfony\Component\Validator\Constraint;

class UniqueConsentTest extends \PHPUnit_Framework_TestCase
{
    public function testGetTargets()
    {
        $constraint = new UniqueConsent();
        $this->assertSame(Constraint::CLASS_CONSTRAINT, $constraint->getTargets());
    }
}
