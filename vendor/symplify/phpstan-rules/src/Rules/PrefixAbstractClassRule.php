<?php

declare(strict_types=1);

namespace Symplify\PHPStanRules\Rules;

use Nette\Utils\Strings;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\Scope;
use PHPStan\Broker\Broker;
use Symplify\Astral\Naming\SimpleNameResolver;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * @see \Symplify\PHPStanRules\Tests\Rules\PrefixAbstractClassRule\PrefixAbstractClassRuleTest
 */
final class PrefixAbstractClassRule extends AbstractSymplifyRule
{
    /**
     * @var string
     */
    public const ERROR_MESSAGE = 'Abstract class name "%s" must be prefixed with "Abstract"';

    /**
     * @var Broker
     */
    private $broker;

    /**
     * @var SimpleNameResolver
     */
    private $simpleNameResolver;

    public function __construct(Broker $broker, SimpleNameResolver $simpleNameResolver)
    {
        $this->broker = $broker;
        $this->simpleNameResolver = $simpleNameResolver;
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
     * @return string[]
     */
    public function process(Node $node, Scope $scope): array
    {
        $className = $this->simpleNameResolver->getName($node);
        if ($className === null) {
            return [];
        }

        $classReflection = $this->broker->getClass($className);
        if (! $classReflection->isAbstract()) {
            return [];
        }

        $shortClassName = (string) $node->name;
        if (Strings::startsWith($shortClassName, 'Abstract')) {
            return [];
        }

        return [sprintf(self::ERROR_MESSAGE, $shortClassName)];
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(self::ERROR_MESSAGE, [
            new CodeSample(
                <<<'CODE_SAMPLE'
abstract class SomeClass
{
}
CODE_SAMPLE
                ,
                <<<'CODE_SAMPLE'
abstract class AbstractSomeClass
{
}
CODE_SAMPLE
            ),
        ]);
    }
}
