<?php

declare(strict_types=1);

namespace Symplify\CodingStandard\Fixer\Commenting;

use Nette\Utils\Strings;
use PhpCsFixer\Fixer\Phpdoc\PhpdocAlignFixer;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use SplFileInfo;
use Symplify\CodingStandard\Fixer\AbstractSymplifyFixer;
use Symplify\CodingStandard\TokenRunner\Contract\DocBlock\MalformWorkerInterface;
use Symplify\CodingStandard\TokenRunner\DocBlock\MalformWorker\InlineVarMalformWorker;
use Symplify\CodingStandard\TokenRunner\DocBlock\MalformWorker\MissingParamNameMalformWorker;
use Symplify\CodingStandard\TokenRunner\DocBlock\MalformWorker\ParamNameTypoMalformWorker;
use Symplify\CodingStandard\TokenRunner\DocBlock\MalformWorker\SuperfluousReturnNameMalformWorker;
use Symplify\CodingStandard\TokenRunner\DocBlock\MalformWorker\SuperfluousVarNameMalformWorker;
use Symplify\CodingStandard\TokenRunner\DocBlock\MalformWorker\SwitchedTypeAndNameMalformWorker;
use Symplify\RuleDocGenerator\Contract\DocumentedRuleInterface;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * @see ParamNameTypoMalformWorker
 * @see InlineVarMalformWorker
 * @see MissingParamNameMalformWorker
 * @see SwitchedTypeAndNameMalformWorker
 * @see SuperfluousReturnNameMalformWorker
 * @see SuperfluousVarNameMalformWorker
 *
 * @see \Symplify\CodingStandard\Tests\Fixer\Commenting\ParamReturnAndVarTagMalformsFixer\ParamReturnAndVarTagMalformsFixerTest
 */
final class ParamReturnAndVarTagMalformsFixer extends AbstractSymplifyFixer implements DocumentedRuleInterface
{
    /**
     * @var string
     */
    private const ERROR_MESSAGE = 'Fixes @param, @return, @var and inline @var annotations broken formats';

    /**
     * @var string
     * @see https://regex101.com/r/8iqNuR/1
     */
    private const TYPE_ANNOTATION_REGEX = '#@(param|return|var)#';

    /**
     * @var MalformWorkerInterface[]
     */
    private $malformWorkers = [];

    /**
     * @param MalformWorkerInterface[] $malformWorkers
     */
    public function __construct(array $malformWorkers)
    {
        $this->malformWorkers = $malformWorkers;
    }

    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(self::ERROR_MESSAGE, []);
    }

    public function isCandidate(Tokens $tokens): bool
    {
        if (! $tokens->isAnyTokenKindsFound([T_DOC_COMMENT, T_COMMENT])) {
            return false;
        }
        return $tokens->isAnyTokenKindsFound([T_FUNCTION, T_VARIABLE]);
    }

    public function fix(SplFileInfo $file, Tokens $tokens): void
    {
        $reversedTokens = $this->reverseTokens($tokens);
        foreach ($reversedTokens as $index => $token) {
            if (! $token->isGivenKind([T_DOC_COMMENT, T_COMMENT])) {
                continue;
            }

            $docContent = $token->getContent();
            if (! Strings::match($docContent, self::TYPE_ANNOTATION_REGEX)) {
                continue;
            }

            $originalDocContent = $docContent;
            foreach ($this->malformWorkers as $malformWorker) {
                $docContent = $malformWorker->work($docContent, $tokens, $index);
            }

            if ($docContent === $originalDocContent) {
                continue;
            }

            $tokens[$index] = new Token([T_DOC_COMMENT, $docContent]);
        }
    }

    public function getPriority(): int
    {
        return $this->getPriorityBefore(PhpdocAlignFixer::class);
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(self::ERROR_MESSAGE, [
            new CodeSample(
                <<<'CODE_SAMPLE'
/**
 * @param string
 */
function getPerson($name)
{
}
CODE_SAMPLE
                ,
                <<<'CODE_SAMPLE'
/**
 * @param string $name
 */
function getPerson($name)
{
}
CODE_SAMPLE
            ),
        ]);
    }
}
