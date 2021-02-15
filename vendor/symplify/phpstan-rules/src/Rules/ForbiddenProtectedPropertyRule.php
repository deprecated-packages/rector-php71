<?php

declare(strict_types=1);

namespace Symplify\PHPStanRules\Rules;

use PhpParser\Node;
use PhpParser\Node\Stmt\Property;
use PHPStan\Analyser\Scope;
use Symplify\PHPStanRules\NodeAnalyzer\ProtectedAnalyzer;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * @see \Symplify\PHPStanRules\Tests\Rules\ForbiddenProtectedPropertyRule\ForbiddenProtectedPropertyRuleTest
 */
final class ForbiddenProtectedPropertyRule extends AbstractSymplifyRule
{
    /**
     * @var string
     */
    public const ERROR_MESSAGE = 'Property with protected modifier is not allowed. Use interface contract method instead';

    /**
     * @var ProtectedAnalyzer
     */
    private $protectedAnalyzer;

    public function __construct(ProtectedAnalyzer $protectedAnalyzer)
    {
        $this->protectedAnalyzer = $protectedAnalyzer;
    }

    /**
     * @return string[]
     */
    public function getNodeTypes(): array
    {
        return [Property::class];
    }

    /**
     * @param Property $node
     * @return string[]
     */
    public function process(Node $node, Scope $scope): array
    {
        if (! $node->isProtected()) {
            return [];
        }

        if ($this->protectedAnalyzer->isProtectedPropertyOrClassConstAllowed($node)) {
            return [];
        }

        return [self::ERROR_MESSAGE];
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(self::ERROR_MESSAGE, [
            new CodeSample(
                <<<'CODE_SAMPLE'
class SomeClass
{
    protected $repository;
}
CODE_SAMPLE
                ,
                <<<'CODE_SAMPLE'
class SomeClass implements RepositoryAwareInterface
{
    public function getRepository()
    {
        // ....
    }
}
CODE_SAMPLE
            ),
        ]);
    }
}
