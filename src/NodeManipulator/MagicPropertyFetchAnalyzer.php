<?php

declare(strict_types=1);

namespace Rector\Core\NodeManipulator;

use PhpParser\Node;
use PhpParser\Node\Expr\PropertyFetch;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\ErrorType;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeWithClassName;
use Rector\Core\Exception\ShouldNotHappenException;
use Rector\NodeNameResolver\NodeNameResolver;
use Rector\NodeTypeResolver\Node\AttributeKey;
use Rector\NodeTypeResolver\NodeTypeResolver;

/**
 * Utils for PropertyFetch Node:
 * "$this->property"
 */
final class MagicPropertyFetchAnalyzer
{
    /**
     * @var NodeTypeResolver
     */
    private $nodeTypeResolver;

    /**
     * @var NodeNameResolver
     */
    private $nodeNameResolver;

    /**
     * @var ReflectionProvider
     */
    private $reflectionProvider;

    public function __construct(NodeNameResolver $nodeNameResolver, NodeTypeResolver $nodeTypeResolver, ReflectionProvider $reflectionProvider)
    {
        $this->nodeTypeResolver = $nodeTypeResolver;
        $this->nodeNameResolver = $nodeNameResolver;
        $this->reflectionProvider = $reflectionProvider;
    }

    public function isMagicOnType(PropertyFetch $propertyFetch, Type $type): bool
    {
        $varNodeType = $this->nodeTypeResolver->resolve($propertyFetch);
        if ($varNodeType instanceof ErrorType) {
            return true;
        }
        if ($varNodeType instanceof MixedType) {
            return false;
        }
        if ($varNodeType->isSuperTypeOf($type)->yes()) {
            return false;
        }
        $nodeName = $this->nodeNameResolver->getName($propertyFetch);
        if ($nodeName === null) {
            return false;
        }
        return ! $this->hasPublicProperty($propertyFetch, $nodeName);
    }

    private function hasPublicProperty(PropertyFetch $propertyFetch, string $propertyName): bool
    {
        $scope = $propertyFetch->getAttribute(AttributeKey::SCOPE);
        if (! $scope instanceof Scope) {
            throw new ShouldNotHappenException();
        }
        $propertyFetchType = $scope->getType($propertyFetch->var);
        if (! $propertyFetchType instanceof TypeWithClassName) {
            return false;
        }
        $propertyFetchType = $propertyFetchType->getClassName();
        if (! $this->reflectionProvider->hasClass($propertyFetchType)) {
            return false;
        }
        $classReflection = $this->reflectionProvider->getClass($propertyFetchType);
        if (! $classReflection->hasProperty($propertyName)) {
            return false;
        }
        $propertyReflection = $classReflection->getProperty($propertyName, $scope);
        return $propertyReflection->isPublic();
    }
}
