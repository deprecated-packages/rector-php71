<?php

declare(strict_types=1);

namespace Symplify\EasyCodingStandard\DependencyInjection\CompilerPass;

use PHP_CodeSniffer\Standards\Generic\Sniffs\PHP\LowerCaseConstantSniff;
use PHP_CodeSniffer\Standards\Generic\Sniffs\PHP\UpperCaseConstantSniff;
use PHP_CodeSniffer\Standards\PSR12\Sniffs\Files\FileHeaderSniff;
use PhpCsFixer\Fixer\Casing\ConstantCaseFixer;
use PhpCsFixer\Fixer\Casing\LowercaseConstantsFixer;
use PhpCsFixer\Fixer\ControlStructure\YodaStyleFixer;
use PhpCsFixer\Fixer\LanguageConstruct\DeclareEqualNormalizeFixer;
use PhpCsFixer\Fixer\Phpdoc\NoBlankLinesAfterPhpdocFixer;
use PhpCsFixer\Fixer\PhpTag\BlankLineAfterOpeningTagFixer;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symplify\EasyCodingStandard\Configuration\Exception\ConflictingCheckersLoadedException;

final class ConflictingCheckersCompilerPass implements CompilerPassInterface
{
    /**
     * These groups do the opposite of each other, e.g. Yoda vs NoYoda.
     *
     * @var string[][]
     */
    private const CONFLICTING_CHECKER_GROUPS = [
        ['SlevomatCodingStandard\Sniffs\ControlStructures\DisallowYodaComparisonSniff', YodaStyleFixer::class],
        [LowerCaseConstantSniff::class, UpperCaseConstantSniff::class],
        [LowercaseConstantsFixer::class, UpperCaseConstantSniff::class],
        [ConstantCaseFixer::class, UpperCaseConstantSniff::class],
        ['SlevomatCodingStandard\Sniffs\TypeHints\DeclareStrictTypesSniff', DeclareEqualNormalizeFixer::class],
        ['SlevomatCodingStandard\Sniffs\TypeHints\DeclareStrictTypesSniff', BlankLineAfterOpeningTagFixer::class],
        [FileHeaderSniff::class, NoBlankLinesAfterPhpdocFixer::class],
    ];

    public function process(ContainerBuilder $containerBuilder): void
    {
        $checkers = $containerBuilder->getServiceIds();
        if ($checkers === []) {
            return;
        }

        foreach (self::CONFLICTING_CHECKER_GROUPS as $viceVersaMatchingCheckerGroup) {
            if (! $this->isMatch($checkers, $viceVersaMatchingCheckerGroup)) {
                continue;
            }

            throw new ConflictingCheckersLoadedException(sprintf(
                'Checkers "%s" mutually exclude each other. Use only one or exclude '
                . 'the unwanted one in "parameters > skip" in your config.',
                implode('" and "', $viceVersaMatchingCheckerGroup)
            ));
        }
    }

    /**
     * @param mixed[] $checkers
     * @param string[] $matchingCheckerGroup
     */
    private function isMatch(array $checkers, array $matchingCheckerGroup): bool
    {
        $checkers = array_flip($checkers);
        $matchingCheckerGroup = array_flip($matchingCheckerGroup);

        $foundCheckers = array_intersect_key($matchingCheckerGroup, $checkers);
        return count($foundCheckers) === count($matchingCheckerGroup);
    }
}
