<?php

declare(strict_types=1);

namespace Rector\DeadDocBlock\Rector\Node;

use Nette\Utils\Strings;
use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\AssignRef;
use PhpParser\Node\Stmt\Echo_;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Foreach_;
use PhpParser\Node\Stmt\If_;
use PhpParser\Node\Stmt\Nop;
use PhpParser\Node\Stmt\Return_;
use PhpParser\Node\Stmt\Static_;
use PhpParser\Node\Stmt\Switch_;
use PhpParser\Node\Stmt\Throw_;
use PhpParser\Node\Stmt\While_;
use PHPStan\PhpDocParser\Ast\PhpDoc\VarTagValueNode;
use Rector\BetterPhpDocParser\PhpDocInfo\PhpDocInfo;
use Rector\Core\Rector\AbstractRector;
use Rector\NodeTypeResolver\Node\AttributeKey;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * @see \Rector\DeadDocBlock\Tests\Rector\Node\RemoveNonExistingVarAnnotationRector\RemoveNonExistingVarAnnotationRectorTest
 *
 * @see https://github.com/phpstan/phpstan/commit/d17e459fd9b45129c5deafe12bca56f30ea5ee99#diff-9f3541876405623b0d18631259763dc1
 */
final class RemoveNonExistingVarAnnotationRector extends AbstractRector
{
    /**
     * @var class-string[]
     */
    private const NODES_TO_MATCH = [
        Assign::class,
        AssignRef::class,
        Foreach_::class,
        Static_::class,
        Echo_::class,
        Return_::class,
        Expression::class,
        Throw_::class,
        If_::class,
        While_::class,
        Switch_::class,
        Nop::class,
    ];

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Removes non-existing @var annotations above the code', [
            new CodeSample(<<<'CODE_SAMPLE'
class SomeClass
{
    public function get()
    {
        /** @var Training[] $trainings */
        return $this->getData();
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
class SomeClass
{
    public function get()
    {
        return $this->getData();
    }
}
CODE_SAMPLE
),

        ]);
    }

    /**
     * @return string[]
     */
    public function getNodeTypes(): array
    {
        return [Node::class];
    }

    public function refactor(Node $node): ?Node
    {
        if ($this->shouldSkip($node)) {
            return null;
        }
        /** @var PhpDocInfo|null $phpDocInfo */
        $phpDocInfo = $node->getAttribute(AttributeKey::PHP_DOC_INFO);
        if ($phpDocInfo === null) {
            return null;
        }
        $attributeAwareVarTagValueNode = $phpDocInfo->getVarTagValueNode();
        if ($attributeAwareVarTagValueNode === null) {
            return null;
        }
        $variableName = $attributeAwareVarTagValueNode->variableName;
        if ($variableName === '') {
            return null;
        }
        $nodeContentWithoutPhpDoc = $this->printWithoutComments($node);
        // it's there
        if (Strings::match($nodeContentWithoutPhpDoc, '#' . preg_quote($variableName, '#') . '\b#')) {
            return null;
        }
        $comments = $node->getComments();
        if (isset($comments[1]) && $comments[1] instanceof Comment) {
            $this->rollbackComments($node, $comments[1]);
            return $node;
        }
        $phpDocInfo->removeByType(VarTagValueNode::class);
        return $node;
    }

    private function shouldSkip(Node $node): bool
    {
        if ($node instanceof Nop && count($node->getComments()) > 1) {
            return true;
        }
        foreach (self::NODES_TO_MATCH as $nodeToMatch) {
            if (! is_a($node, $nodeToMatch, true)) {
                continue;
            }

            return false;
        }
        return true;
    }
}
