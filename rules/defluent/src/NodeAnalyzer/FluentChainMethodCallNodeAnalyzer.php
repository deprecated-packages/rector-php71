<?php

declare(strict_types=1);

namespace Rector\Defluent\NodeAnalyzer;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeWithClassName;
use Rector\Core\Util\StaticInstanceOf;
use Rector\NodeNameResolver\NodeNameResolver;
use Rector\NodeTypeResolver\Node\AttributeKey;
use Rector\NodeTypeResolver\NodeTypeResolver;

/**
 * Utils for chain of MethodCall Node:
 * "$this->methodCall()->chainedMethodCall()"
 */
final class FluentChainMethodCallNodeAnalyzer
{
    /**
     * Types that look like fluent interface, but actually create a new object.
     * Should be skipped, as they return different object. Not an fluent interface!
     *
     * @var string[]
     */
    private const KNOWN_FACTORY_FLUENT_TYPES = ['PHPStan\Analyser\MutatingScope'];

    /**
     * @var NodeTypeResolver
     */
    private $nodeTypeResolver;

    /**
     * @var NodeNameResolver
     */
    private $nodeNameResolver;

    public function __construct(NodeNameResolver $nodeNameResolver, NodeTypeResolver $nodeTypeResolver)
    {
        $this->nodeTypeResolver = $nodeTypeResolver;
        $this->nodeNameResolver = $nodeNameResolver;
    }

    /**
     * Checks that in:
     * $this->someCall();
     *
     * The method is fluent class method === returns self
     * public function someClassMethod()
     * {
     *      return $this;
     * }
     */
    public function isFluentClassMethodOfMethodCall(MethodCall $methodCall): bool
    {
        if ($this->isCall($methodCall->var)) {
            return false;
        }
        $calleeStaticType = $this->nodeTypeResolver->getStaticType($methodCall->var);
        // we're not sure
        if ($calleeStaticType instanceof MixedType) {
            return false;
        }
        $methodReturnStaticType = $this->nodeTypeResolver->getStaticType($methodCall);
        // is fluent type
        if (! $calleeStaticType->equals($methodReturnStaticType)) {
            return false;
        }
        if ($calleeStaticType instanceof TypeWithClassName) {
            foreach (self::KNOWN_FACTORY_FLUENT_TYPES as $knownFactoryFluentTypes) {
                if (is_a($calleeStaticType->getClassName(), $knownFactoryFluentTypes, true)) {
                    return false;
                }
            }
        }
        return true;
    }

    public function isLastChainMethodCall(MethodCall $methodCall): bool
    {
        // is chain method call
        if (! $methodCall->var instanceof MethodCall && ! $methodCall->var instanceof New_) {
            return false;
        }
        $nextNode = $methodCall->getAttribute(AttributeKey::NEXT_NODE);
        // is last chain call
        return ! $nextNode instanceof Node;
    }

    /**
     * @return string[]|null[]
     */
    public function collectMethodCallNamesInChain(MethodCall $desiredMethodCall): array
    {
        $methodCalls = $this->collectAllMethodCallsInChain($desiredMethodCall);
        $methodNames = [];
        foreach ($methodCalls as $methodCall) {
            $methodNames[] = $this->nodeNameResolver->getName($methodCall->name);
        }
        return $methodNames;
    }

    /**
     * @return MethodCall[]
     */
    public function collectAllMethodCallsInChain(MethodCall $methodCall): array
    {
        $chainMethodCalls = [$methodCall];
        // traverse up
        $currentNode = $methodCall->var;
        while ($currentNode instanceof MethodCall) {
            $chainMethodCalls[] = $currentNode;
            $currentNode = $currentNode->var;
        }
        // traverse down
        if (count($chainMethodCalls) === 1) {
            $currentNode = $methodCall->getAttribute(AttributeKey::PARENT_NODE);
            while ($currentNode instanceof MethodCall) {
                $chainMethodCalls[] = $currentNode;
                $currentNode = $currentNode->getAttribute(AttributeKey::PARENT_NODE);
            }
        }
        return $chainMethodCalls;
    }

    /**
     * @return MethodCall[]
     */
    public function collectAllMethodCallsInChainWithoutRootOne(MethodCall $methodCall): array
    {
        $chainMethodCalls = $this->collectAllMethodCallsInChain($methodCall);
        foreach ($chainMethodCalls as $key => $chainMethodCall) {
            if (! $chainMethodCall->var instanceof MethodCall && ! $chainMethodCall->var instanceof New_) {
                unset($chainMethodCalls[$key]);
                break;
            }
        }
        return array_values($chainMethodCalls);
    }

    /**
     * Checks "$this->someMethod()->anotherMethod()"
     *
     * @param string[] $methods
     */
    public function isTypeAndChainCalls(Node $node, Type $type, array $methods): bool
    {
        if (! $node instanceof MethodCall) {
            return false;
        }
        // node chaining is in reverse order than code
        $methods = array_reverse($methods);
        foreach ($methods as $method) {
            if (! $this->nodeNameResolver->isName($node->name, $method)) {
                return false;
            }

            $node = $node->var;
        }
        $variableType = $this->nodeTypeResolver->resolve($node);
        if ($variableType instanceof MixedType) {
            return false;
        }
        return $variableType->isSuperTypeOf($type)
            ->yes();
    }

    public function resolveRootExpr(MethodCall $methodCall): Node
    {
        $callerNode = $methodCall->var;
        while ($callerNode instanceof MethodCall || $callerNode instanceof StaticCall) {
            $callerNode = $callerNode instanceof StaticCall ? $callerNode->class : $callerNode->var;
        }
        return $callerNode;
    }

    public function resolveRootMethodCall(MethodCall $methodCall): ?MethodCall
    {
        $callerNode = $methodCall->var;
        while ($callerNode instanceof MethodCall && $callerNode->var instanceof MethodCall) {
            $callerNode = $callerNode->var;
        }
        if ($callerNode instanceof MethodCall) {
            return $callerNode;
        }
        return null;
    }

    private function isCall(Expr $expr): bool
    {
        return StaticInstanceOf::isOneOf($expr, [MethodCall::class, StaticCall::class]);
    }
}
