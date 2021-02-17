<?php

declare(strict_types=1);

namespace Rector\CodeQuality\Rector\Isset_;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp\BooleanAnd;
use PhpParser\Node\Expr\BinaryOp\NotIdentical;
use PhpParser\Node\Expr\Isset_;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Property;
use PHPStan\Type\TypeWithClassName;
use Rector\Core\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * @see \Rector\CodeQuality\Tests\Rector\Isset_\IssetOnPropertyObjectToPropertyExistsRector\IssetOnPropertyObjectToPropertyExistsRectorTest
 * @see https://3v4l.org/TI8XL Change isset on property object to property_exists() with not null check
 */
final class IssetOnPropertyObjectToPropertyExistsRector extends AbstractRector
{
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Change isset on property object to property_exists() and not null check', [
            new CodeSample(<<<'CODE_SAMPLE'
class SomeClass
{
private $x;

public function run(): void
{
    isset($this->x);
}
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
class SomeClass
{
private $x;

public function run(): void
{
    property_exists($this, 'x') && $this->x !== null;
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
        return [Isset_::class];
    }

    /**
     * @param Isset_ $node
     */
    public function refactor(Node $node): ?Node
    {
        $newNodes = [];
        foreach ($node->vars as $issetVar) {
            if (! $issetVar instanceof PropertyFetch) {
                continue;
            }

            $property = $this->nodeRepository->findPropertyByPropertyFetch($issetVar);
            if ($property instanceof Property && $property->type) {
                continue;
            }

            $propertyFetchName = $this->getName($issetVar->name);
            if ($propertyFetchName === null) {
                continue;
            }

            $propertyFetchVarType = $this->getObjectType($issetVar->var);
            if ($propertyFetchVarType instanceof TypeWithClassName && property_exists($propertyFetchVarType->getClassName(), $propertyFetchName)) {
                $newNodes[] = $this->createNotIdenticalToNull($issetVar);
                continue;
            }

            $newNodes[] = $this->replaceToPropertyExistsWithNullCheck($issetVar->var, $propertyFetchName, $issetVar);
        }
        return $this->nodeFactory->createReturnBooleanAnd($newNodes);
    }

    private function replaceToPropertyExistsWithNullCheck(Expr $expr, string $property, PropertyFetch $propertyFetch): BooleanAnd
    {
        $args = [new Arg($expr), new Arg(new String_($property))];
        $propertyExistsFuncCall = $this->nodeFactory->createFuncCall('property_exists', $args);
        return new BooleanAnd($propertyExistsFuncCall, $this->createNotIdenticalToNull($propertyFetch));
    }

    private function createNotIdenticalToNull(Expr $expr): NotIdentical
    {
        return new NotIdentical($expr, $this->nodeFactory->createNull());
    }
}
