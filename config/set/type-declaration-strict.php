<?php

declare(strict_types=1);

return static function (\Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator $containerConfigurator) : void {
    $services = $containerConfigurator->services();
    $services->set(\Rector\TypeDeclaration\Rector\Closure\AddClosureReturnTypeRector::class);
    $services->set(\Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromStrictTypedPropertyRector::class);
    $services->set(\Rector\TypeDeclaration\Rector\Property\TypedPropertyFromStrictConstructorRector::class);
    $services->set(\Rector\TypeDeclaration\Rector\ClassMethod\ParamTypeFromStrictTypedPropertyRector::class);
};
