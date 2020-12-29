<?php

declare(strict_types=1);

namespace Rector\PhpAttribute;

use PhpParser\Node;
use PhpParser\Node\Attribute;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Property;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagValueNode;
use Rector\BetterPhpDocParser\PhpDocInfo\PhpDocInfo;
use Rector\NodeTypeResolver\Node\AttributeKey;
use Rector\PhpAttribute\Contract\PhpAttributableTagNodeInterface;
use Rector\PhpAttribute\Printer\PhpAttributteGroupFactory;
use Rector\Testing\PHPUnit\StaticPHPUnitEnvironment;

final class AnnotationToAttributeConverter
{
    /**
     * @var PhpAttributteGroupFactory
     */
    private $phpAttributteGroupFactory;

    public function __construct(PhpAttributteGroupFactory $phpAttributteGroupFactory)
    {
        $this->phpAttributteGroupFactory = $phpAttributteGroupFactory;
    }

    /**
     * @param Class_|Property|ClassMethod|Function_|Closure|ArrowFunction $node
     */
    public function convertNode(Node $node): ?Node
    {
        /** @var PhpDocInfo|null $phpDocInfo */
        $phpDocInfo = $node->getAttribute(AttributeKey::PHP_DOC_INFO);
        if ($phpDocInfo === null) {
            return null;
        }
        $hasNewAttrGroups = false;
        // 0. has 0 nodes, nothing to change
        /** @var PhpAttributableTagNodeInterface[] $phpAttributableTagNodes */
        $phpAttributableTagNodes = $phpDocInfo->findAllByType(PhpAttributableTagNodeInterface::class);
        if ($phpAttributableTagNodes === []) {
            return null;
        }
        // 1. keep only those, whom's attribute class exists
        $phpAttributableTagNodes = $this->filterOnlyExistingAttributes($phpAttributableTagNodes);
        if ($phpAttributableTagNodes !== []) {
            $hasNewAttrGroups = true;
        }
        // 2. remove tags
        foreach ($phpAttributableTagNodes as $phpAttributableTagNode) {
            /** @var PhpDocTagValueNode $phpAttributableTagNode */
            $phpDocInfo->removeTagValueNodeFromNode($phpAttributableTagNode);
        }
        // 3. convert annotations to attributes
        $newAttrGroups = $this->phpAttributteGroupFactory->create($phpAttributableTagNodes);
        $node->attrGroups = array_merge($node->attrGroups, $newAttrGroups);
        if ($hasNewAttrGroups) {
            return $node;
        }
        return null;
    }

    /**
     * @param PhpAttributableTagNodeInterface[] $phpAttributableTagNodes
     * @return PhpAttributableTagNodeInterface[]
     */
    private function filterOnlyExistingAttributes(array $phpAttributableTagNodes): array
    {
        if (StaticPHPUnitEnvironment::isPHPUnitRun()) {
            return $phpAttributableTagNodes;
        }
        return array_filter($phpAttributableTagNodes, function (PhpAttributableTagNodeInterface $phpAttributableTagNode): bool {
            return class_exists($phpAttributableTagNode->getAttributeClassName());
        });
    }
}
