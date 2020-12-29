<?php

declare(strict_types=1);

namespace Rector\Core\Rector\AbstractRector;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\ClassLike;
use Rector\CodingStyle\Naming\ClassNaming;
use Rector\NodeNameResolver\NodeNameResolver;
use Rector\NodeTypeResolver\Node\AttributeKey;

/**
 * This could be part of @see AbstractRector, but decopuling to trait
 * makes clear what code has 1 purpose.
 */
trait NameResolverTrait
{
    /**
     * @var NodeNameResolver
     */
    private $nodeNameResolver;

    /**
     * @var ClassNaming
     */
    private $classNaming;

    /**
     * @required
     */
    public function autowireNameResolverTrait(NodeNameResolver $nodeNameResolver, ClassNaming $classNaming): void
    {
        $this->nodeNameResolver = $nodeNameResolver;
        $this->classNaming = $classNaming;
    }

    public function isName(Node $node, string $name): bool
    {
        return $this->nodeNameResolver->isName($node, $name);
    }

    public function areNamesEqual(Node $firstNode, Node $secondNode): bool
    {
        return $this->nodeNameResolver->areNamesEqual($firstNode, $secondNode);
    }

    /**
     * @param string[] $names
     */
    public function isNames(Node $node, array $names): bool
    {
        return $this->nodeNameResolver->isNames($node, $names);
    }

    public function getName(Node $node): ?string
    {
        return $this->nodeNameResolver->getName($node);
    }

    /**
     * @param string|Name|Identifier|ClassLike $name
     */
    protected function getShortName($name): string
    {
        return $this->classNaming->getShortName($name);
    }

    protected function isLocalPropertyFetchNamed(Node $node, string $name): bool
    {
        return $this->nodeNameResolver->isLocalPropertyFetchNamed($node, $name);
    }

    protected function isLocalMethodCallNamed(Node $node, string $name): bool
    {
        if (! $node instanceof MethodCall) {
            return false;
        }
        if ($node->var instanceof StaticCall) {
            return false;
        }
        if ($node->var instanceof MethodCall) {
            return false;
        }
        if (! $this->isName($node->var, 'this')) {
            return false;
        }
        return $this->isName($node->name, $name);
    }

    /**
     * @param string[] $names
     */
    protected function isLocalMethodCallsNamed(Node $node, array $names): bool
    {
        foreach ($names as $name) {
            if ($this->isLocalMethodCallNamed($node, $name)) {
                return true;
            }
        }
        return false;
    }

    protected function isFuncCallName(Node $node, string $name): bool
    {
        if (! $node instanceof FuncCall) {
            return false;
        }
        return $this->isName($node, $name);
    }

    /**
     * Detects "SomeClass::class"
     */
    protected function isClassConstReference(Node $node, string $className): bool
    {
        if (! $node instanceof ClassConstFetch) {
            return false;
        }
        if (! $this->isName($node->name, 'class')) {
            return false;
        }
        return $this->isName($node->class, $className);
    }

    protected function isStaticCallNamed(Node $node, string $className, string $methodName): bool
    {
        if (! $node instanceof StaticCall) {
            return false;
        }
        // handles (new Some())->...
        if ($node->class instanceof Expr) {
            if (! $this->isObjectType($node->class, $className)) {
                return false;
            }
        } elseif (! $this->isName($node->class, $className)) {
            return false;
        }
        return $this->isName($node->name, $methodName);
    }

    /**
     * @param string[] $methodNames
     */
    protected function isStaticCallsNamed(Node $node, string $className, array $methodNames): bool
    {
        foreach ($methodNames as $methodName) {
            if ($this->isStaticCallNamed($node, $className, $methodName)) {
                return true;
            }
        }
        return false;
    }

    protected function isMethodCall(Node $node, string $variableName, string $methodName): bool
    {
        if (! $node instanceof MethodCall) {
            return false;
        }
        if ($node->var instanceof MethodCall) {
            return false;
        }
        if ($node->var instanceof StaticCall) {
            return false;
        }
        if (! $this->isName($node->var, $variableName)) {
            return false;
        }
        return $this->isName($node->name, $methodName);
    }

    protected function isVariableName(Node $node, string $name): bool
    {
        if (! $node instanceof Variable) {
            return false;
        }
        return $this->isName($node, $name);
    }

    protected function isInClassNamed(Node $node, string $desiredClassName): bool
    {
        $className = $node->getAttribute(AttributeKey::CLASS_NAME);
        if ($className === null) {
            return false;
        }
        return is_a($className, $desiredClassName, true);
    }

    /**
     * @param string[] $desiredClassNames
     */
    protected function isInClassesNamed(Node $node, array $desiredClassNames): bool
    {
        foreach ($desiredClassNames as $desiredClassName) {
            if ($this->isInClassNamed($node, $desiredClassName)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param string[] $names
     */
    protected function isFuncCallNames(Node $node, array $names): bool
    {
        return $this->nodeNameResolver->isFuncCallNames($node, $names);
    }
}
