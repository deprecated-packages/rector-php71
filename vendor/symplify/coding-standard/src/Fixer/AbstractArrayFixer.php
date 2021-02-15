<?php

declare(strict_types=1);

namespace Symplify\CodingStandard\Fixer;

use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixer\WhitespacesFixerConfig;
use SplFileInfo;
use Symplify\CodingStandard\Contract\ArrayFixerInterface;
use Symplify\CodingStandard\Fixer\LineLength\LineLengthFixer;
use Symplify\CodingStandard\TokenRunner\Analyzer\FixerAnalyzer\ArrayAnalyzer;
use Symplify\CodingStandard\TokenRunner\Analyzer\FixerAnalyzer\BlockFinder;
use Symplify\CodingStandard\TokenRunner\ValueObject\BlockInfo;

abstract class AbstractArrayFixer extends AbstractSymplifyFixer implements ArrayFixerInterface
{
    /**
     * @var int[]
     */
    protected const ARRAY_OPEN_TOKENS = [T_ARRAY, CT::T_ARRAY_SQUARE_BRACE_OPEN];

    /**
     * @var WhitespacesFixerConfig
     */
    protected $whitespacesFixerConfig;

    /**
     * @var ArrayAnalyzer
     */
    protected $arrayAnalyzer;

    /**
     * @var BlockFinder
     */
    private $blockFinder;

    /**
     * @required
     */
    public function autowireAbstractArrayFixer(
        BlockFinder $blockFinder,
        WhitespacesFixerConfig $whitespacesFixerConfig,
        ArrayAnalyzer $arrayAnalyzer
    ): void {
        $this->blockFinder = $blockFinder;
        $this->whitespacesFixerConfig = $whitespacesFixerConfig;
        $this->arrayAnalyzer = $arrayAnalyzer;
    }

    public function isCandidate(Tokens $tokens): bool
    {
        if (! $tokens->isAnyTokenKindsFound(self::ARRAY_OPEN_TOKENS)) {
            return false;
        }
        return $tokens->isTokenKindFound(T_DOUBLE_ARROW);
    }

    public function fix(SplFileInfo $file, Tokens $tokens): void
    {
        $reversedTokens = $this->reverseTokens($tokens);

        foreach ($reversedTokens as $index => $token) {
            if (! $token->isGivenKind(self::ARRAY_OPEN_TOKENS)) {
                continue;
            }

            $blockInfo = $this->blockFinder->findInTokensByEdge($tokens, $index);
            if (! $blockInfo instanceof BlockInfo) {
                continue;
            }

            $this->fixArrayOpener($tokens, $blockInfo, $index);
        }
    }

    public function getPriority(): int
    {
        // to handle the indent
        return $this->getPriorityBefore(LineLengthFixer::class);
    }
}
