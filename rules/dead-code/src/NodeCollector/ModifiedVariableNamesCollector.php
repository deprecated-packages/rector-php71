<?php

declare(strict_types=1);

namespace Rector\DeadCode\NodeCollector;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt;
use Rector\Core\PhpParser\NodeTraverser\CallableNodeTraverser;
use Rector\NodeNameResolver\NodeNameResolver;
use Rector\NodeTypeResolver\Node\AttributeKey;

final class ModifiedVariableNamesCollector
{
    /**
     * @var CallableNodeTraverser
     */
    private $callableNodeTraverser;

    /**
     * @var NodeNameResolver
     */
    private $nodeNameResolver;

    public function __construct(CallableNodeTraverser $callableNodeTraverser, NodeNameResolver $nodeNameResolver)
    {
        $this->callableNodeTraverser = $callableNodeTraverser;
        $this->nodeNameResolver = $nodeNameResolver;
    }

    /**
     * @return string[]
     */
    public function collectModifiedVariableNames(Stmt $stmt): array
    {
        $argNames = $this->collectFromArgs($stmt);
        $assignNames = $this->collectFromAssigns($stmt);
        return array_merge($argNames, $assignNames);
    }

    /**
     * @return string[]
     */
    private function collectFromArgs(Stmt $stmt): array
    {
        $variableNames = [];
        $this->callableNodeTraverser->traverseNodesWithCallable($stmt, function (Node $node) use (&$variableNames) {
            if (! $node instanceof Arg) {
                return null;
            }
            if (! $this->isVariableChangedInReference($node)) {
                return null;
            }
            $variableName = $this->nodeNameResolver->getName($node->value);
            if ($variableName === null) {
                return null;
            }
            $variableNames[] = $variableName;
        });
        return $variableNames;
    }

    /**
     * @return string[]
     */
    private function collectFromAssigns(Stmt $stmt): array
    {
        $modifiedVariableNames = [];
        $this->callableNodeTraverser->traverseNodesWithCallable($stmt, function (Node $node) use (&$modifiedVariableNames) {
            if (! $node instanceof Assign) {
                return null;
            }
            if (! $node->var instanceof Variable) {
                return null;
            }
            $variableName = $this->nodeNameResolver->getName($node->var);
            if ($variableName === null) {
                return null;
            }
            $modifiedVariableNames[] = $variableName;
        });
        return $modifiedVariableNames;
    }

    private function isVariableChangedInReference(Arg $arg): bool
    {
        $parentNode = $arg->getAttribute(AttributeKey::PARENT_NODE);
        if (! $parentNode instanceof FuncCall) {
            return false;
        }
        return $this->nodeNameResolver->isNames($parentNode, ['array_shift', 'array_pop']);
    }
}
