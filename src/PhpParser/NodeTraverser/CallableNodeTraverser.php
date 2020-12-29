<?php

declare(strict_types=1);

namespace Rector\Core\PhpParser\NodeTraverser;

use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor;
use PhpParser\NodeVisitorAbstract;

final class CallableNodeTraverser
{
    /**
     * @param Node|Node[]|null $nodes
     */
    public function traverseNodesWithCallable($nodes, callable $callable): void
    {
        if ($nodes === []) {
            return;
        }
        if ($nodes === null) {
            return;
        }
        if (! is_array($nodes)) {
            $nodes = [$nodes];
        }
        $nodeTraverser = new NodeTraverser();
        $callableNodeVisitor = $this->createNodeVisitor($callable);
        $nodeTraverser->addVisitor($callableNodeVisitor);
        $nodeTraverser->traverse($nodes);
    }

    private function createNodeVisitor(callable $callable): NodeVisitor
    {
        return new class($callable) extends NodeVisitorAbstract
        {
            /**
             * @var callable
             */
            private $callable;
            public function __construct(callable $callable)
            {
                $this->callable = $callable;
            }
            /**
             * @return int|Node|null
             */
            public function enterNode(Node $node)
            {
                $callable = $this->callable;
                return $callable($node);
            }
        };
    }
}
