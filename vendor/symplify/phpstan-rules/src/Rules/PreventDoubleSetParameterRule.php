<?php

declare(strict_types=1);

namespace Symplify\PHPStanRules\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use Symplify\Astral\Naming\SimpleNameResolver;
use Symplify\Astral\NodeValue\NodeValueResolver;
use Symplify\PHPStanRules\NodeAnalyzer\Symfony\SymfonyPhpConfigClosureAnalyzer;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * @see \Symplify\PHPStanRules\Tests\Rules\PreventDoubleSetParameterRule\PreventDoubleSetParameterRuleTest
 */
final class PreventDoubleSetParameterRule extends AbstractSymplifyRule
{
    /**
     * @var string
     */
    public const ERROR_MESSAGE = 'Set param value is overriden. Merge it to previous set above';

    /**
     * @var SimpleNameResolver
     */
    private $simpleNameResolver;

    /**
     * @var SymfonyPhpConfigClosureAnalyzer
     */
    private $symfonyPhpConfigClosureAnalyzer;

    /**
     * @var array<string, string[]>
     */
    private $setParametersNamesByFile = [];

    /**
     * @var NodeValueResolver
     */
    private $nodeValueResolver;

    public function __construct(
        SimpleNameResolver $simpleNameResolver,
        SymfonyPhpConfigClosureAnalyzer $symfonyPhpConfigClosureAnalyzer,
        NodeValueResolver $nodeValueResolver
    ) {
        $this->simpleNameResolver = $simpleNameResolver;
        $this->symfonyPhpConfigClosureAnalyzer = $symfonyPhpConfigClosureAnalyzer;
        $this->nodeValueResolver = $nodeValueResolver;
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
     * @return string[]
     */
    public function process(Node $node, Scope $scope): array
    {
        if (! $this->symfonyPhpConfigClosureAnalyzer->isSymfonyPhpConfigScope($scope)) {
            return [];
        }

        if (! $this->simpleNameResolver->isName($node->name, 'set')) {
            return [];
        }

        if (! $this->simpleNameResolver->isName($node->var, 'parameters')) {
            return [];
        }

        $setParameterName = $this->nodeValueResolver->resolve($node->args[0]->value, $scope->getFile());
        if ($setParameterName === null) {
            return [];
        }

        $previousSetParameterNames = $this->setParametersNamesByFile[$scope->getFile()] ?? [];

        if (in_array($setParameterName, $previousSetParameterNames, true)) {
            return [self::ERROR_MESSAGE];
        }

        $this->setParametersNamesByFile[$scope->getFile()][] = $setParameterName;

        return [];
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(self::ERROR_MESSAGE, [
            new CodeSample(
                <<<'CODE_SAMPLE'
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $parameters = $containerConfigurator->parameters();

    $parameters->set('some_param', [1]);
    $parameters->set('some_param', [2]);
};
CODE_SAMPLE
                ,
                <<<'CODE_SAMPLE'
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $parameters = $containerConfigurator->parameters();

    $parameters->set('some_param', [1, 2]);
};
CODE_SAMPLE
            ),
        ]);
    }
}
