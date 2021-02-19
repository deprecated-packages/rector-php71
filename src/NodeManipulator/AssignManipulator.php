<?php

declare(strict_types=1);

namespace Rector\Core\NodeManipulator;

use PhpParser\Node;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\AssignOp;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\List_;
use PhpParser\Node\Expr\PostDec;
use PhpParser\Node\Expr\PostInc;
use PhpParser\Node\Expr\PreDec;
use PhpParser\Node\Expr\PreInc;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\FunctionLike;
use PhpParser\Node\Stmt\Expression;
use Rector\Core\NodeAnalyzer\PropertyFetchAnalyzer;
use Rector\Core\PhpParser\Node\BetterNodeFinder;
use Rector\Core\PhpParser\Printer\BetterStandardPrinter;
use Rector\NodeNameResolver\NodeNameResolver;
use Rector\NodeTypeResolver\Node\AttributeKey;
use Symplify\PackageBuilder\Php\TypeChecker;

final class AssignManipulator
{
    /**
     * @var array<class-string<Node>>
     */
    private const MODIFYING_NODE_TYPES = [
        AssignOp::class,
        PreDec::class,
        PostDec::class,
        PreInc::class,
        PostInc::class,
    ];

    /**
     * @var NodeNameResolver
     */
    private $nodeNameResolver;

    /**
     * @var BetterStandardPrinter
     */
    private $betterStandardPrinter;

    /**
     * @var BetterNodeFinder
     */
    private $betterNodeFinder;

    /**
     * @var PropertyFetchAnalyzer
     */
    private $propertyFetchAnalyzer;

    /**
     * @var TypeChecker
     */
    private $typeChecker;

    public function __construct(BetterStandardPrinter $betterStandardPrinter, NodeNameResolver $nodeNameResolver, BetterNodeFinder $betterNodeFinder, PropertyFetchAnalyzer $propertyFetchAnalyzer, TypeChecker $typeChecker)
    {
        $this->nodeNameResolver = $nodeNameResolver;
        $this->betterStandardPrinter = $betterStandardPrinter;
        $this->betterNodeFinder = $betterNodeFinder;
        $this->propertyFetchAnalyzer = $propertyFetchAnalyzer;
        $this->typeChecker = $typeChecker;
    }

    /**
     * Matches:
     * each() = [1, 2];
     */
    public function isListToEachAssign(Assign $assign): bool
    {
        if (! $assign->expr instanceof FuncCall) {
            return false;
        }
        if (! $assign->var instanceof List_) {
            return false;
        }
        return $this->nodeNameResolver->isName($assign->expr, 'each');
    }

    public function isLeftPartOfAssign(Node $node): bool
    {
        $parent = $node->getAttribute(AttributeKey::PARENT_NODE);
        if ($parent instanceof Assign && $this->betterStandardPrinter->areNodesEqual($parent->var, $node)) {
            return true;
        }
        if ($parent !== null && $this->typeChecker->isInstanceOf($parent, self::MODIFYING_NODE_TYPES)) {
            return true;
        }
        // traverse up to array dim fetches
        if ($parent instanceof ArrayDimFetch) {
            $previousParent = $parent;
            while ($parent instanceof ArrayDimFetch) {
                $previousParent = $parent;
                $parent = $parent->getAttribute(AttributeKey::PARENT_NODE);
            }

            if ($parent instanceof Assign) {
                return $parent->var === $previousParent;
            }
        }
        return false;
    }

    public function isNodePartOfAssign(Node $node): bool
    {
        $previousNode = $node;
        $parentNode = $node->getAttribute(AttributeKey::PARENT_NODE);
        while ($parentNode !== null && ! $parentNode instanceof Expression) {
            if ($parentNode instanceof Assign && $this->betterStandardPrinter->areNodesEqual($parentNode->var, $previousNode)) {
                return true;
            }

            $previousNode = $parentNode;
            $parentNode = $parentNode->getAttribute(AttributeKey::PARENT_NODE);
        }
        return false;
    }

    /**
     * @return PropertyFetch[]
     */
    public function resolveAssignsToLocalPropertyFetches(FunctionLike $functionLike): array
    {
        return $this->betterNodeFinder->find((array) $functionLike->getStmts(), function (Node $node): bool {
            if (! $this->propertyFetchAnalyzer->isLocalPropertyFetch($node)) {
                return false;
            }
            return $this->isLeftPartOfAssign($node);
        });
    }
}
