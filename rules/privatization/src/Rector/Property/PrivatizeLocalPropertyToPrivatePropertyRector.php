<?php

declare(strict_types=1);

namespace Rector\Privatization\Rector\Property;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\Property;
use Rector\Core\Rector\AbstractRector;
use Rector\NodeTypeResolver\Node\AttributeKey;
use Rector\PhpAttribute\ValueObject\TagName;
use Rector\VendorLocker\NodeVendorLocker\PropertyVisibilityVendorLockResolver;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * @see \Rector\Privatization\Tests\Rector\Property\PrivatizeLocalPropertyToPrivatePropertyRector\PrivatizeLocalPropertyToPrivatePropertyRectorTest
 */
final class PrivatizeLocalPropertyToPrivatePropertyRector extends AbstractRector
{
    /**
     * @var string[]
     */
    private const ANNOTATIONS_REQUIRING_PUBLIC = [
        TagName::API,
        // Symfony DI
        TagName::REQUIRED,
        // other DI
        TagName::INJECT,
    ];

    /**
     * @var PropertyVisibilityVendorLockResolver
     */
    private $propertyVisibilityVendorLockResolver;

    public function __construct(PropertyVisibilityVendorLockResolver $propertyVisibilityVendorLockResolver)
    {
        $this->propertyVisibilityVendorLockResolver = $propertyVisibilityVendorLockResolver;
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Privatize local-only property to private property', [
            new CodeSample(<<<'CODE_SAMPLE'
class SomeClass
{
    public $value;

    public function run()
    {
        return $this->value;
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
class SomeClass
{
    private $value;

    public function run()
    {
        return $this->value;
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
        return [Property::class];
    }

    /**
     * @param Property $node
     */
    public function refactor(Node $node): ?Node
    {
        if ($this->shouldSkip($node)) {
            return null;
        }
        $propertyFetches = $this->nodeRepository->findPropertyFetchesByProperty($node);
        $usedPropertyFetchClassNames = [];
        foreach ($propertyFetches as $propertyFetch) {
            $usedPropertyFetchClassNames[] = $propertyFetch->getAttribute(AttributeKey::CLASS_NAME);
        }
        $usedPropertyFetchClassNames = array_unique($usedPropertyFetchClassNames);
        $propertyClassName = $node->getAttribute(AttributeKey::CLASS_NAME);
        // has external usage
        if ([$propertyClassName] !== $usedPropertyFetchClassNames) {
            return null;
        }
        $this->makePrivate($node);
        return $node;
    }

    private function shouldSkip(Property $property): bool
    {
        $classLike = $property->getAttribute(AttributeKey::CLASS_NODE);
        if (! $classLike instanceof ClassLike) {
            return true;
        }
        if ($this->shouldSkipClass($classLike)) {
            return true;
        }
        if ($this->shouldSkipProperty($property)) {
            return true;
        }
        // is parent required property? skip it
        if ($this->propertyVisibilityVendorLockResolver->isParentLockedProperty($property)) {
            return true;
        }
        if ($this->propertyVisibilityVendorLockResolver->isChildLockedProperty($property)) {
            return true;
        }
        $phpDocInfo = $this->phpDocInfoFactory->createFromNodeOrEmpty($property);
        foreach (self::ANNOTATIONS_REQUIRING_PUBLIC as $annotationRequiringPublic) {
            if ($phpDocInfo->hasByName($annotationRequiringPublic)) {
                return true;
            }
        }
        return false;
    }

    private function shouldSkipClass(ClassLike $classLike): bool
    {
        if (! $classLike instanceof Class_) {
            return true;
        }
        if ($this->isAnonymousClass($classLike)) {
            return true;
        }
        return $this->isObjectTypes($classLike, ['PHPUnit\Framework\TestCase', 'PHP_CodeSniffer\Sniffs\Sniff']);
    }

    private function shouldSkipProperty(Property $property): bool
    {
        // already private
        if ($property->isPrivate()) {
            return true;
        }
        // skip for now
        return $property->isStatic();
    }
}
