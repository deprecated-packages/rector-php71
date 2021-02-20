<?php

use Rector\Transform\Rector\Class_\MergeInterfacesRector;
use Rector\Transform\Tests\Rector\Class_\MergeInterfacesRector\Source\SomeInterface;
use Rector\Transform\Tests\Rector\Class_\MergeInterfacesRector\Source\SomeOldInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();
    $services->set(MergeInterfacesRector::class)
        ->call('configure', [[
            MergeInterfacesRector::OLD_TO_NEW_INTERFACES => [
                SomeOldInterface::class => SomeInterface::class,
            ],
        ]]);
};
