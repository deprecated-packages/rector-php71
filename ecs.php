<?php

declare(strict_types=1);

use PHP_CodeSniffer\Standards\PSR2\Sniffs\Methods\MethodDeclarationSniff;
use PhpCsFixer\Fixer\Basic\Psr4Fixer;
use PhpCsFixer\Fixer\ClassNotation\ClassAttributesSeparationFixer;
use PhpCsFixer\Fixer\Import\GlobalNamespaceImportFixer;
use PhpCsFixer\Fixer\Operator\UnaryOperatorSpacesFixer;
use PhpCsFixer\Fixer\Phpdoc\GeneralPhpdocAnnotationRemoveFixer;
use PhpCsFixer\Fixer\Phpdoc\NoSuperfluousPhpdocTagsFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocTypesFixer;
use PhpCsFixer\Fixer\PhpTag\BlankLineAfterOpeningTagFixer;
use PhpCsFixer\Fixer\PhpUnit\PhpUnitStrictFixer;
use PhpCsFixer\Fixer\ReturnNotation\ReturnAssignmentFixer;
use PhpCsFixer\Fixer\Strict\StrictComparisonFixer;
use PhpCsFixer\Fixer\Whitespace\ArrayIndentationFixer;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symplify\CodingStandard\Fixer\ArrayNotation\ArrayListItemNewlineFixer;
use Symplify\CodingStandard\Fixer\ArrayNotation\ArrayOpenerAndCloserNewlineFixer;
use Symplify\CodingStandard\Fixer\ArrayNotation\StandaloneLineInMultilineArrayFixer;
use Symplify\CodingStandard\Fixer\Commenting\RemoveCommentedCodeFixer;
use Symplify\CodingStandard\Fixer\LineLength\LineLengthFixer;
use Symplify\EasyCodingStandard\ValueObject\Option;
use Symplify\EasyCodingStandard\ValueObject\Set\SetList;

return static function (ContainerConfigurator $containerConfigurator) : void {
    $services = $containerConfigurator->services();
    $services->set(StandaloneLineInMultilineArrayFixer::class);
    $services->set(ArrayOpenerAndCloserNewlineFixer::class);
    $services->set(ClassAttributesSeparationFixer::class);
    $services->set(GeneralPhpdocAnnotationRemoveFixer::class)->call('configure', [[
        'annotations' => ['throws', 'author', 'package', 'group'],
    ]]);
    $services->set(LineLengthFixer::class);
    $services->set(NoSuperfluousPhpdocTagsFixer::class)->call('configure', [[
        'allow_mixed' => true,
    ]]);
    $parameters = $containerConfigurator->parameters();
    $parameters->set(Option::PATHS, [
        __DIR__ . '/bin',
        __DIR__ . '/src',
        __DIR__ . '/packages',
        __DIR__ . '/rules',
        __DIR__ . '/tests',
        __DIR__ . '/utils',
        __DIR__ . '/config',
        __DIR__ . '/ecs.php',
        __DIR__ . '/rector.php',
        __DIR__ . '/config/set',
    ]);
    $parameters->set(Option::SETS, [SetList::PSR_12, SetList::SYMPLIFY, SetList::COMMON, SetList::CLEAN_CODE]);
    $parameters->set(Option::SKIP, [
        '*/Source/*',
        '*/Fixture/*',
        '*/Expected/*',
        # generated from /vendor
        __DIR__ . '/packages/doctrine-annotation-generated/src/ConstantPreservingDocParser.php',
        __DIR__ . '/packages/doctrine-annotation-generated/src/ConstantPreservingAnnotationReader.php',
        // template files
        __DIR__ . '/packages/rector-generator/templates',

        // broken
        GlobalNamespaceImportFixer::class,
        MethodDeclarationSniff::class . '.Underscore',
        UnaryOperatorSpacesFixer::class,
        // breaks on-purpose annotated variables
        ReturnAssignmentFixer::class,
        // buggy with specific markdown snippet file in docs/rules_overview.md
        ArrayListItemNewlineFixer::class,
        BlankLineAfterOpeningTagFixer::class,
        Psr4Fixer::class,

        // buggy - fix on master
        RemoveCommentedCodeFixer::class,

        PhpdocTypesFixer::class => [__DIR__ . '/rules/php74/src/Rector/Double/RealToFloatTypeCastRector.php'],
        PhpUnitStrictFixer::class => [
            __DIR__ . '/packages/better-php-doc-parser/tests/PhpDocInfo/PhpDocInfo/PhpDocInfoTest.php',
            __DIR__ . '/tests/PhpParser/Node/NodeFactoryTest.php',
            '*TypeResolverTest.php',
        ],

        StrictComparisonFixer::class => [__DIR__ . '/rules/polyfill/src/ConditionEvaluator.php'],

        // bugged for some reason
        ArrayIndentationFixer::class => [
            __DIR__ . '/rules/order/src/Rector/Class_/OrderPublicInterfaceMethodRector.php',
        ],
    ]);
    $parameters->set(Option::LINE_ENDING, "\n");
};
