<?php

declare(strict_types=1);

use PHP_CodeSniffer\Standards\Squiz\Sniffs\WhiteSpace\LanguageConstructSpacingSniff;
use PHP_CodeSniffer\Standards\Squiz\Sniffs\WhiteSpace\SuperfluousWhitespaceSniff;
use PhpCsFixer\Fixer\CastNotation\CastSpacesFixer;
use PhpCsFixer\Fixer\ClassNotation\ClassAttributesSeparationFixer;
use PhpCsFixer\Fixer\ClassNotation\NoBlankLinesAfterClassOpeningFixer;
use PhpCsFixer\Fixer\ClassNotation\SingleTraitInsertPerStatementFixer;
use PhpCsFixer\Fixer\FunctionNotation\FunctionTypehintSpaceFixer;
use PhpCsFixer\Fixer\FunctionNotation\MethodArgumentSpaceFixer;
use PhpCsFixer\Fixer\FunctionNotation\ReturnTypeDeclarationFixer;
use PhpCsFixer\Fixer\NamespaceNotation\NoLeadingNamespaceWhitespaceFixer;
use PhpCsFixer\Fixer\Operator\BinaryOperatorSpacesFixer;
use PhpCsFixer\Fixer\Operator\ConcatSpaceFixer;
use PhpCsFixer\Fixer\Operator\NotOperatorWithSuccessorSpaceFixer;
use PhpCsFixer\Fixer\Operator\TernaryOperatorSpacesFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocSingleLineVarSpacingFixer;
use PhpCsFixer\Fixer\Semicolon\NoSinglelineWhitespaceBeforeSemicolonsFixer;
use PhpCsFixer\Fixer\Semicolon\SpaceAfterSemicolonFixer;
use PhpCsFixer\Fixer\Whitespace\MethodChainingIndentationFixer;
use PhpCsFixer\Fixer\Whitespace\NoSpacesAroundOffsetFixer;
use PhpCsFixer\Fixer\Whitespace\NoWhitespaceInBlankLineFixer;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symplify\CodingStandard\Fixer\Spacing\NewlineServiceDefinitionConfigFixer;
use Symplify\CodingStandard\Fixer\Spacing\StandaloneLinePromotedPropertyFixer;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services->set(StandaloneLinePromotedPropertyFixer::class);

    $services->set(NewlineServiceDefinitionConfigFixer::class);

    $services->set(MethodChainingIndentationFixer::class);

    $services->set(ClassAttributesSeparationFixer::class)
        ->call('configure', [[
            'elements' => ['const', 'property', 'method'],
        ]]);

    $services->set(ConcatSpaceFixer::class)
        ->call('configure', [[
            'spacing' => 'one',
        ]]);

    $services->set(NotOperatorWithSuccessorSpaceFixer::class);

    $services->set(SuperfluousWhitespaceSniff::class)
        ->property('ignoreBlankLines', false);

    $services->set(CastSpacesFixer::class);

    $services->set(BinaryOperatorSpacesFixer::class)
        ->call('configure', [[
            'operators' => [
                '=>' => 'single_space',
                '=' => 'single_space',
            ],
        ]]);

    $services->set(ClassAttributesSeparationFixer::class);

    $services->set(SingleTraitInsertPerStatementFixer::class);

    $services->set(FunctionTypehintSpaceFixer::class);

    $services->set(NoBlankLinesAfterClassOpeningFixer::class);

    $services->set(NoSinglelineWhitespaceBeforeSemicolonsFixer::class);

    $services->set(PhpdocSingleLineVarSpacingFixer::class);

    $services->set(NoLeadingNamespaceWhitespaceFixer::class);

    $services->set(NoSpacesAroundOffsetFixer::class);

    $services->set(NoWhitespaceInBlankLineFixer::class);

    $services->set(ReturnTypeDeclarationFixer::class);

    $services->set(SpaceAfterSemicolonFixer::class);

    $services->set(TernaryOperatorSpacesFixer::class);

    $services->set(MethodArgumentSpaceFixer::class);

    $services->set(LanguageConstructSpacingSniff::class);
};
