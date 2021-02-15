<?php

declare(strict_types=1);

namespace Symplify\CodingStandard\TokenRunner\Analyzer\FixerAnalyzer;

use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use Symplify\CodingStandard\TokenRunner\Exception\TokenNotFoundException;
use Symplify\CodingStandard\TokenRunner\ValueObject\BlockInfo;
use Symplify\SymplifyKernel\Exception\ShouldNotHappenException;

final class TokenSkipper
{
    /**
     * @var BlockFinder
     */
    private $blockFinder;

    public function __construct(BlockFinder $blockFinder)
    {
        $this->blockFinder = $blockFinder;
    }

    public function skipBlocks(Tokens $tokens, int $position): int
    {
        if (! isset($tokens[$position])) {
            throw new TokenNotFoundException($position);
        }

        $token = $tokens[$position];

        if ($token->getContent() === '{') {
            $blockInfo = $this->blockFinder->findInTokensByEdge($tokens, $position);
            if (! $blockInfo instanceof BlockInfo) {
                return $position;
            }

            return $blockInfo->getEnd();
        }

        if ($token->isGivenKind([CT::T_ARRAY_SQUARE_BRACE_OPEN, T_ARRAY])) {
            $blockInfo = $this->blockFinder->findInTokensByEdge($tokens, $position);
            if (! $blockInfo instanceof BlockInfo) {
                return $position;
            }

            return $blockInfo->getEnd();
        }

        return $position;
    }

    public function skipBlocksReversed(Tokens $tokens, int $position): int
    {
        /** @var Token $token */
        $token = $tokens[$position];
        if (! $token->isGivenKind(CT::T_ARRAY_SQUARE_BRACE_CLOSE) && ! $token->equals(')')) {
            return $position;
        }

        $blockInfo = $this->blockFinder->findInTokensByEdge($tokens, $position);
        if (! $blockInfo instanceof BlockInfo) {
            throw new ShouldNotHappenException();
        }

        return $blockInfo->getStart();
    }
}
