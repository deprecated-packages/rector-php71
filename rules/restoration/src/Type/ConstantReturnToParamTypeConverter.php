<?php

declare(strict_types=1);

namespace Rector\Restoration\Type;

use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Generic\GenericClassStringType;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
use Rector\NodeTypeResolver\PHPStan\Type\TypeFactory;

final class ConstantReturnToParamTypeConverter
{
    /**
     * @var TypeFactory
     */
    private $typeFactory;

    public function __construct(TypeFactory $typeFactory)
    {
        $this->typeFactory = $typeFactory;
    }

    public function convert(Type $type): Type
    {
        if (! $type instanceof ConstantStringType && ! $type instanceof ConstantArrayType) {
            return new MixedType();
        }
        return $this->unwrapConstantTypeToObjectType($type);
    }

    private function unwrapConstantTypeToObjectType(Type $type): Type
    {
        if ($type instanceof ConstantArrayType) {
            return $this->unwrapConstantTypeToObjectType($type->getItemType());
        }
        if ($type instanceof ConstantStringType) {
            return new ObjectType($type->getValue());
        }
        if ($type instanceof GenericClassStringType && $type->getGenericType() instanceof ObjectType) {
            return $type->getGenericType();
        }
        if ($type instanceof UnionType) {
            return $this->unwrapUnionType($type);
        }
        return new MixedType();
    }

    private function unwrapUnionType(UnionType $unionType): Type
    {
        $types = [];
        foreach ($unionType->getTypes() as $unionedType) {
            $unionType = $this->unwrapConstantTypeToObjectType($unionedType);
            if ($unionType !== null) {
                $types[] = $unionType;
            }
        }
        return $this->typeFactory->createMixedPassedOrUnionType($types);
    }
}
