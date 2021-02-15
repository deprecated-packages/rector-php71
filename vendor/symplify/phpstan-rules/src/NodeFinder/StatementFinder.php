<?php

declare(strict_types=1);

namespace Symplify\PHPStanRules\NodeFinder;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Assign;
use PhpParser\NodeFinder;
use Symplify\Astral\Naming\SimpleNameResolver;
use Symplify\PHPStanRules\ValueObject\PHPStanAttributeKey;

final class StatementFinder
{
    /**
     * @var NodeFinder
     */
    private $nodeFinder;

    /**
     * @var SimpleNameResolver
     */
    private $simpleNameResolver;

    public function __construct(SimpleNameResolver $simpleNameResolver, NodeFinder $nodeFinder)
    {
        $this->nodeFinder = $nodeFinder;
        $this->simpleNameResolver = $simpleNameResolver;
    }

    public function isUsedInNextStatement(Assign $assign, Node $node): bool
    {
        $var = $assign->var;
        $varClass = get_class($var);
        $next = $node->getAttribute(PHPStanAttributeKey::NEXT);
        $parentOfParentAssignment = $node->getAttribute(PHPStanAttributeKey::PARENT);

        while ($next) {
            $nextVars = $this->nodeFinder->findInstanceOf($next, $varClass);
            if ($this->hasSameVar($nextVars, $parentOfParentAssignment, $var)) {
                return true;
            }

            $next = $next->getAttribute(PHPStanAttributeKey::NEXT);
        }

        return false;
    }

    /**
     * @param Node[] $nodes
     */
    private function hasSameVar(array $nodes, Node $parentOfParentAssignNode, Expr $varExpr): bool
    {
        foreach ($nodes as $node) {
            $parent = $node->getAttribute(PHPStanAttributeKey::PARENT);
            $parentOfParentNode = $parent->getAttribute(PHPStanAttributeKey::PARENT);

            if (! $this->simpleNameResolver->areNamesEqual($node, $varExpr)) {
                continue;
            }

            if ($parentOfParentNode !== $parentOfParentAssignNode) {
                return true;
            }
        }

        return false;
    }
}
