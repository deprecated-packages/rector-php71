<?php

declare(strict_types=1);

namespace Rector\Symfony5\Rector\MethodCall;

use PhpParser\Node;
use PhpParser\Node\Expr\BooleanNot;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\MethodCall;
use Rector\Core\Rector\AbstractRector;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\Definition;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * @see https://github.com/symfony/symfony/blob/5.x/UPGRADE-5.2.md#dependencyinjection
 * @see \Rector\Symfony5\Tests\Rector\MethodCall\DefinitionAliasSetPrivateToSetPublicRector\DefinitionAliasSetPrivateToSetPublicRectorTest
 */
final class DefinitionAliasSetPrivateToSetPublicRector extends AbstractRector
{
    /**
     * @var class-string[]
     */
    private const REQUIRED_CLASS_TYPES = [Definition::class, Alias::class];

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Migrates from deprecated Definition/Alias->setPrivate() to Definition/Alias->setPublic()', [
            new CodeSample(<<<'CODE_SAMPLE'
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\Definition;

class SomeClass
{
    public function run()
    {
        $definition = new Definition('Example\Foo');
        $definition->setPrivate(false);

        $alias = new Alias('Example\Foo');
        $alias->setPrivate(false);
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\Definition;

class SomeClass
{
    public function run()
    {
        $definition = new Definition('Example\Foo');
        $definition->setPublic(true);

        $alias = new Alias('Example\Foo');
        $alias->setPublic(true);
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
        return [MethodCall::class];
    }

    /**
     * @param MethodCall $node
     */
    public function refactor(Node $node): ?Node
    {
        if (! $this->isObjectTypes($node->var, self::REQUIRED_CLASS_TYPES)) {
            return null;
        }
        if (! $this->isName($node->name, 'setPrivate')) {
            return null;
        }
        $argValue = $node->args[0]->value;
        $argValue = $argValue instanceof ConstFetch
            ? $this->createNegationConsFetch($argValue)
            : new BooleanNot($argValue);
        return $this->nodeFactory->createMethodCall($node->var, 'setPublic', [$argValue]);
    }

    private function createNegationConsFetch(ConstFetch $constFetch): ConstFetch
    {
        if ($this->isFalse($constFetch)) {
            return $this->nodeFactory->createTrue();
        }
        return $this->nodeFactory->createFalse();
    }
}
