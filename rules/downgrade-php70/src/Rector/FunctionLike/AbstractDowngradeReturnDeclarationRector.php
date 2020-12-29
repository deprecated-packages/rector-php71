<?php

declare(strict_types=1);

namespace Rector\DowngradePhp70\Rector\FunctionLike;

use PhpParser\Node;
use PhpParser\Node\FunctionLike;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\IterableType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\UnionType;
use Rector\BetterPhpDocParser\PhpDocInfo\PhpDocInfo;
use Rector\Core\Rector\AbstractRector;
use Rector\DowngradePhp70\Contract\Rector\DowngradeReturnDeclarationRectorInterface;
use Rector\NodeTypeResolver\Node\AttributeKey;
use Traversable;

abstract class AbstractDowngradeReturnDeclarationRector extends AbstractRector implements DowngradeReturnDeclarationRectorInterface
{
    /**
     * @return string[]
     */
    public function getNodeTypes(): array
    {
        return [Function_::class, ClassMethod::class];
    }

    /**
     * @param ClassMethod|Function_ $node
     */
    public function refactor(Node $node): ?Node
    {
        if (! $this->shouldRemoveReturnDeclaration($node)) {
            return null;
        }
        $this->decorateFunctionLikeWithReturnTagValueNode($node);
        $node->returnType = null;
        return $node;
    }

    /**
     * @param ClassMethod|Function_ $functionLike
     */
    private function decorateFunctionLikeWithReturnTagValueNode(FunctionLike $functionLike): void
    {
        /** @var PhpDocInfo|null $phpDocInfo */
        $phpDocInfo = $functionLike->getAttribute(AttributeKey::PHP_DOC_INFO);
        if ($phpDocInfo === null) {
            $phpDocInfo = $this->phpDocInfoFactory->createEmpty($functionLike);
        }
        if ($functionLike->returnType === null) {
            return;
        }
        $type = $this->staticTypeMapper->mapPhpParserNodePHPStanType($functionLike->returnType);
        if ($type instanceof IterableType) {
            $type = new UnionType([$type, new IntersectionType([new ObjectType(Traversable::class)])]);
        }
        $phpDocInfo->changeReturnType($type);
    }
}
