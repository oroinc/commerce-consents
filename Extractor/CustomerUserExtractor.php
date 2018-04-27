<?php

namespace Oro\Bundle\ConsentBundle\Extractor;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Component\PropertyAccess\PropertyAccessor;

/**
 * Extract customerUser object from object that contains relation on it
 */
class CustomerUserExtractor
{
    /**
     * @var [
     *   'class_name' => 'property_path_string',
     *    ...
     * ]
     */
    private $mappings = [];

    /**
     * @var PropertyAccessor
     */
    private $propertyAccessor;

    public function __construct()
    {
        $this->propertyAccessor = new PropertyAccessor();
    }

    /**
     * @param string $className
     * @param string $propertyPath
     */
    public function addMapping($className, $propertyPath)
    {
        $this->mappings[$className] = $propertyPath;
    }

    /**
     * @param object $object
     *
     * @return null|CustomerUser
     */
    public function extract($object)
    {
        if (!is_object($object)) {
            return null;
        }

        $filteredMappings = array_filter($this->mappings, function ($entityClassName) use ($object) {
            return is_a($object, $entityClassName);
        }, ARRAY_FILTER_USE_KEY);

        if (empty($filteredMappings)) {
            return null;
        }

        foreach ($filteredMappings as $propertyPath) {
            if ($this->propertyAccessor->isReadable($object, $propertyPath)) {
                $customerUser = $this->propertyAccessor->getValue($object, $propertyPath);

                return $customerUser instanceof CustomerUser ? $customerUser : null;
            }
        }

        return null;
    }
}
