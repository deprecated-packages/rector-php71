<?php

declare(strict_types=1);

namespace Rector\Legacy\Rector\Class_;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use Rector\Core\Rector\AbstractRector;
use Rector\Core\ValueObject\MethodName;
use Rector\Legacy\NodeAnalyzer\SingletonClassMethodAnalyzer;
use Rector\Legacy\ValueObject\PropertyAndClassMethodName;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * @see https://3v4l.org/lifbH
 * @see https://stackoverflow.com/a/203359/1348344
 * @see http://cleancode.blog/2017/07/20/how-to-avoid-many-instances-in-singleton-pattern/
 * @see \Rector\Legacy\Tests\Rector\Class_\ChangeSingletonToServiceRector\ChangeSingletonToServiceRectorTest
 */
final class ChangeSingletonToServiceRector extends AbstractRector
{
    /**
     * @var SingletonClassMethodAnalyzer
     */
    private $singletonClassMethodAnalyzer;

    public function __construct(SingletonClassMethodAnalyzer $singletonClassMethodAnalyzer)
    {
        $this->singletonClassMethodAnalyzer = $singletonClassMethodAnalyzer;
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Change singleton class to normal class that can be registered as a service', [
            new CodeSample(<<<'CODE_SAMPLE'
class SomeClass
{
    private static $instance;

    private function __construct()
    {
    }

    public static function getInstance()
    {
        if (null === static::$instance) {
            static::$instance = new static();
        }

        return static::$instance;
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
class SomeClass
{
    public function __construct()
    {
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
        if ($node->isAnonymous()) {
            return null;
        }
        $propertyAndClassMethodName = $this->matchStaticPropertyFetchAndGetSingletonMethodName($node);
        if ($propertyAndClassMethodName === null) {
            return null;
        }
        return $this->refactorClassStmts($node, $propertyAndClassMethodName);
    }

    private function matchStaticPropertyFetchAndGetSingletonMethodName(Class_ $class): ?PropertyAndClassMethodName
    {
        foreach ($class->getMethods() as $classMethod) {
            if (! $classMethod->isStatic()) {
                continue;
            }

            $staticPropertyFetch = $this->singletonClassMethodAnalyzer->matchStaticPropertyFetch($classMethod);
            if ($staticPropertyFetch === null) {
                return null;
            }

            /** @var string $propertyName */
            $propertyName = $this->getName($staticPropertyFetch);

            /** @var string $classMethodName */
            $classMethodName = $this->getName($classMethod);

            return new PropertyAndClassMethodName($propertyName, $classMethodName);
        }
        return null;
    }

    private function refactorClassStmts(Class_ $class, PropertyAndClassMethodName $propertyAndClassMethodName): Class_
    {
        foreach ($class->getMethods() as $classMethod) {
            if ($this->isName($classMethod, $propertyAndClassMethodName->getClassMethodName())) {
                $this->removeNodeFromStatements($class, $classMethod);
                continue;
            }

            if (! $this->isNames($classMethod, [MethodName::CONSTRUCT, MethodName::CLONE, '__wakeup'])) {
                continue;
            }

            if ($classMethod->isPublic()) {
                continue;
            }

            // remove non-public empty
            if ($classMethod->stmts === []) {
                $this->removeNodeFromStatements($class, $classMethod);
            } else {
                $this->makePublic($classMethod);
            }
        }
        $this->removePropertyByName($class, $propertyAndClassMethodName->getPropertyName());
        return $class;
    }

    private function removePropertyByName(Class_ $class, string $propertyName): void
    {
        foreach ($class->getProperties() as $property) {
            if (! $this->isName($property, $propertyName)) {
                continue;
            }

            $this->removeNodeFromStatements($class, $property);
        }
    }
}
