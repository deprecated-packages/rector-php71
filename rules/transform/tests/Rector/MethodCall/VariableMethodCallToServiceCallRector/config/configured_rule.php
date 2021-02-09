<?php

use Rector\Transform\Rector\MethodCall\VariableMethodCallToServiceCallRector;
use Rector\Transform\ValueObject\VariableMethodCallToServiceCall;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symplify\SymfonyPhpConfig\ValueObjectInliner;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();
    $services->set(VariableMethodCallToServiceCallRector::class)->call(
        'configure',
        [[
            VariableMethodCallToServiceCallRector::VARIABLE_METHOD_CALLS_TO_SERVICE_CALLS => ValueObjectInliner::inline([
                

























                new VariableMethodCallToServiceCall(
                    'PhpParser\Node',
                    'getAttribute',
                    'php_doc_info',
                    'Rector\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory',
                    'createFromNodeOrEmpty'
                ),

























                
            ]),
        ]]
    );
};
