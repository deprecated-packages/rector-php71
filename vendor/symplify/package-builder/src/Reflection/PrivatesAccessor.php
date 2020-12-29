<?php

declare(strict_types=1);

namespace Symplify\PackageBuilder\Reflection;

use ReflectionProperty;
use Symplify\PHPStanRules\Exception\ShouldNotHappenException;

/**
 * @see \Symplify\PackageBuilder\Tests\Reflection\PrivatesAccessorTest
 */
final class PrivatesAccessor
{
    /**
     * @param object $object
     */
    public function getPrivateProperty($object, string $propertyName)
    {
        if (property_exists($object, $propertyName)) {
            $propertyReflection = new ReflectionProperty($object, $propertyName);
        } else {
            $parentClass = get_parent_class($object);
            if ($parentClass === false) {
                throw new ShouldNotHappenException();
            }

            $propertyReflection = new ReflectionProperty($parentClass, $propertyName);
        }
        $propertyReflection->setAccessible(true);
        return $propertyReflection->getValue($object);
    }

    /**
     * @param object $object
     */
    public function setPrivateProperty($object, string $propertyName, $value): void
    {
        $propertyReflection = new ReflectionProperty(get_class($object), $propertyName);
        $propertyReflection->setAccessible(true);
        $propertyReflection->setValue($object, $value);
    }
}
