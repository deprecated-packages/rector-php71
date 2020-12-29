<?php

declare(strict_types=1);

use Rector\DeadCode\Rector\Class_\RemoveUnusedClassesRector;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator) : void {
    $services = $containerConfigurator->services();
    $services->set(RemoveUnusedClassesRector::class);
};
