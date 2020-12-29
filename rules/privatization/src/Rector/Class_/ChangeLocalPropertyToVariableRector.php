<?php

declare(strict_types=1);

namespace Rector\Privatization\Rector\Class_;

use PhpParser\Node;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Do_;
use PhpParser\Node\Stmt\Else_;
use PhpParser\Node\Stmt\If_;
use PhpParser\Node\Stmt\While_;
use PhpParser\NodeTraverser;
use Rector\Core\PhpParser\Node\Manipulator\ClassManipulator;
use Rector\Core\PhpParser\Node\Manipulator\PropertyFetchManipulator;
use Rector\Core\Rector\AbstractRector;
use Rector\NodeTypeResolver\Node\AttributeKey;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * @see \Rector\Privatization\Tests\Rector\Class_\ChangeLocalPropertyToVariableRector\ChangeLocalPropertyToVariableRectorTest
 */
final class ChangeLocalPropertyToVariableRector extends AbstractRector
{
    /**
     * @var string[]
     */
    private const SCOPE_CHANGING_NODE_TYPES = [Do_::class, While_::class, If_::class, Else_::class];

    /**
     * @var ClassManipulator
     */
    private $classManipulator;

    /**
     * @var PropertyFetchManipulator
     */
    private $propertyFetchManipulator;

    public function __construct(ClassManipulator $classManipulator, PropertyFetchManipulator $propertyFetchManipulator)
    {
        $this->classManipulator = $classManipulator;
        $this->propertyFetchManipulator = $propertyFetchManipulator;
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Change local property used in single method to local variable', [
            new CodeSample(<<<'CODE_SAMPLE'
class SomeClass
{
    private $count;
    public function run()
    {
        $this->count = 5;
        return $this->count;
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
class SomeClass
{
    public function run()
    {
        $count = 5;
        return $count;
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
        return [Class_::class];
    }

    /**
     * @param Class_ $node
     */
    public function refactor(Node $node): ?Node
    {
        if ($this->isAnonymousClass($node)) {
            return null;
        }
        $privatePropertyNames = $this->classManipulator->getPrivatePropertyNames($node);
        $propertyUsageByMethods = $this->collectPropertyFetchByMethods($node, $privatePropertyNames);
        foreach ($propertyUsageByMethods as $propertyName => $methodNames) {
            if (count($methodNames) === 1) {
                continue;
            }

            unset($propertyUsageByMethods[$propertyName]);
        }
        $this->replacePropertyFetchesByLocalProperty($node, $propertyUsageByMethods);
        // remove properties
        foreach ($node->getProperties() as $property) {
            $classMethodNames = array_keys($propertyUsageByMethods);
            if (! $this->isNames($property, $classMethodNames)) {
                continue;
            }

            $this->removeNode($property);
        }
        return $node;
    }

    /**
     * @param string[] $privatePropertyNames
     * @return string[][]
     */
    private function collectPropertyFetchByMethods(Class_ $class, array $privatePropertyNames): array
    {
        $propertyUsageByMethods = [];
        foreach ($privatePropertyNames as $privatePropertyName) {
            foreach ($class->getMethods() as $classMethod) {
                $hasProperty = (bool) $this->betterNodeFinder->findFirst($classMethod, function (Node $node) use ($privatePropertyName): bool {
                    if (! $node instanceof PropertyFetch) {
                        return false;
                    }
                    return $this->isName($node->name, $privatePropertyName);
                });

                if (! $hasProperty) {
                    continue;
                }

                $isPropertyChangingInMultipleMethodCalls = $this->isPropertyChangingInMultipleMethodCalls($classMethod, $privatePropertyName);

                if ($isPropertyChangingInMultipleMethodCalls) {
                    continue;
                }

                /** @var string $classMethodName */
                $classMethodName = $this->getName($classMethod);
                $propertyUsageByMethods[$privatePropertyName][] = $classMethodName;
            }
        }
        return $propertyUsageByMethods;
    }

    /**
     * @param string[][] $propertyUsageByMethods
     */
    private function replacePropertyFetchesByLocalProperty(Class_ $class, array $propertyUsageByMethods): void
    {
        foreach ($propertyUsageByMethods as $propertyName => $methodNames) {
            $methodName = $methodNames[0];
            $classMethod = $class->getMethod($methodName);
            if ($classMethod === null) {
                continue;
            }

            $this->traverseNodesWithCallable((array) $classMethod->getStmts(), function (Node $node) use ($propertyName): ?Variable {
                if (! $node instanceof PropertyFetch) {
                    return null;
                }
                if (! $this->isName($node, $propertyName)) {
                    return null;
                }
                return new Variable($propertyName);
            });
        }
    }

    /**
     * Covers https://github.com/rectorphp/rector/pull/2558#discussion_r363036110
     */
    private function isPropertyChangingInMultipleMethodCalls(ClassMethod $classMethod, string $privatePropertyName): bool
    {
        $isPropertyChanging = false;
        $isPropertyReadInIf = false;
        $isIfFollowedByAssign = false;
        $this->traverseNodesWithCallable((array) $classMethod->getStmts(), function (Node $node) use (&$isPropertyChanging, $privatePropertyName, &$isPropertyReadInIf, &$isIfFollowedByAssign): ?int {
            if ($isPropertyReadInIf) {
                if (! $this->propertyFetchManipulator->isLocalPropertyOfNames($node, [$privatePropertyName])) {
                    return null;
                }

                $parentNode = $node->getAttribute(AttributeKey::PARENT_NODE);
                if ($parentNode instanceof Assign && $parentNode->var === $node) {
                    $isIfFollowedByAssign = true;
                }
            }
            if (! $this->isScopeChangingNode($node)) {
                return null;
            }
            if ($node instanceof If_) {
                $isPropertyReadInIf = $this->refactorIf($node, $privatePropertyName);
            }
            $isPropertyChanging = $this->isPropertyChanging($node, $this, $privatePropertyName);
            if (! $isPropertyChanging) {
                return null;
            }
            return NodeTraverser::STOP_TRAVERSAL;
        });
        return $isPropertyChanging || $isIfFollowedByAssign;
    }

    private function isScopeChangingNode(Node $node): bool
    {
        foreach (self::SCOPE_CHANGING_NODE_TYPES as $scopeChangingNode) {
            if (! is_a($node, $scopeChangingNode, true)) {
                continue;
            }

            return true;
        }
        return false;
    }

    private function refactorIf(If_ $if, string $privatePropertyName): ?bool
    {
        $this->traverseNodesWithCallable($if->cond, function (Node $node) use ($privatePropertyName, &$isPropertyReadInIf): ?int {
            if (! $this->propertyFetchManipulator->isLocalPropertyOfNames($node, [$privatePropertyName])) {
                return null;
            }
            $isPropertyReadInIf = true;
            return NodeTraverser::STOP_TRAVERSAL;
        });
        return $isPropertyReadInIf;
    }

    /**
     * @param mixed $this__
     */
    private function isPropertyChanging(Node $node, $this__, string $privatePropertyName): bool
    {
        $isPropertyChanging = false;
        // here cannot be any property assign
        $this__->traverseNodesWithCallable($node, function (Node $node) use (&$isPropertyChanging, $privatePropertyName): ?int {
            if (! $node instanceof Assign) {
                return null;
            }
            if (! $node->var instanceof PropertyFetch) {
                return null;
            }
            if (! $this->isName($node->var->name, $privatePropertyName)) {
                return null;
            }
            $isPropertyChanging = true;
            return NodeTraverser::STOP_TRAVERSAL;
        });
        return $isPropertyChanging;
    }
}
