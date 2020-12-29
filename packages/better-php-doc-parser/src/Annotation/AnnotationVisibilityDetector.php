<?php

declare(strict_types=1);

namespace Rector\BetterPhpDocParser\Annotation;

final class AnnotationVisibilityDetector
{
    /**
     * @param object $annotation
     */
    public function isPrivate($annotation): bool
    {
        $publicPropertiesCount = count(get_object_vars($annotation));
        /** @noRector special way to get public property count from object */
        $propertiesCount = count((array) $annotation);
        return $publicPropertiesCount !== $propertiesCount;
    }
}
