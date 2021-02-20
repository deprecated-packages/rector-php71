<?php

declare(strict_types=1);

namespace Rector\PHPStanStaticTypeMapper\TypeMapper;

use PhpParser\Node;
use PhpParser\Node\Name;
use PHPStan\PhpDocParser\Ast\Type\ArrayTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Generic\GenericClassStringType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
use Rector\AttributeAwarePhpDoc\Ast\Type\AttributeAwareArrayTypeNode;
use Rector\AttributeAwarePhpDoc\Ast\Type\AttributeAwareGenericTypeNode;
use Rector\AttributeAwarePhpDoc\Ast\Type\AttributeAwareIdentifierTypeNode;
use Rector\AttributeAwarePhpDoc\Ast\Type\AttributeAwareUnionTypeNode;
use Rector\BetterPhpDocParser\Contract\PhpDocNode\AttributeAwareNodeInterface;
use Rector\PHPStanStaticTypeMapper\Contract\TypeMapperInterface;
use Rector\PHPStanStaticTypeMapper\PHPStanStaticTypeMapper;
use Rector\PHPStanStaticTypeMapper\TypeAnalyzer\UnionTypeCommonTypeNarrower;
use Rector\TypeDeclaration\TypeNormalizer;
use Symplify\PackageBuilder\Reflection\ClassLikeExistenceChecker;

/**
 * @see \Rector\PHPStanStaticTypeMapper\Tests\TypeMapper\ArrayTypeMapperTest
 */
final class ArrayTypeMapper implements TypeMapperInterface
{
    /**
     * @var string
     */
    public const HAS_GENERIC_TYPE_PARENT = 'has_generic_type_parent';

    /**
     * @var PHPStanStaticTypeMapper
     */
    private $phpStanStaticTypeMapper;

    /**
     * @var TypeNormalizer
     */
    private $typeNormalizer;

    /**
     * @var UnionTypeCommonTypeNarrower
     */
    private $unionTypeCommonTypeNarrower;

    /**
     * @var ClassLikeExistenceChecker
     */
    private $classLikeExistenceChecker;

    /**
     * @required
     */
    public function autowireArrayTypeMapper(PHPStanStaticTypeMapper $phpStanStaticTypeMapper, TypeNormalizer $typeNormalizer, UnionTypeCommonTypeNarrower $unionTypeCommonTypeNarrower, ClassLikeExistenceChecker $classLikeExistenceChecker): void
    {
        $this->phpStanStaticTypeMapper = $phpStanStaticTypeMapper;
        $this->typeNormalizer = $typeNormalizer;
        $this->unionTypeCommonTypeNarrower = $unionTypeCommonTypeNarrower;
        $this->classLikeExistenceChecker = $classLikeExistenceChecker;
    }

    public function getNodeClass(): string
    {
        return ArrayType::class;
    }

    /**
     * @param ArrayType $type
     */
    public function mapToPHPStanPhpDocTypeNode(Type $type): TypeNode
    {
        $itemType = $type->getItemType();
        if ($itemType instanceof UnionType && ! $type instanceof ConstantArrayType) {
            return $this->createArrayTypeNodeFromUnionType($itemType);
        }
        if ($itemType instanceof ArrayType && $this->isGenericArrayCandidate($itemType)) {
            return $this->createGenericArrayType($type, true);
        }
        if ($this->isGenericArrayCandidate($type)) {
            return $this->createGenericArrayType($type, true);
        }
        $narrowedTypeNode = $this->narrowConstantArrayTypeOfUnionType($type, $itemType);
        if ($narrowedTypeNode instanceof TypeNode) {
            return $narrowedTypeNode;
        }
        $itemTypeNode = $this->phpStanStaticTypeMapper->mapToPHPStanPhpDocTypeNode($itemType);
        return new AttributeAwareArrayTypeNode($itemTypeNode);
    }

    /**
     * @param ArrayType $type
     */
    public function mapToPhpParserNode(Type $type, ?string $kind = null): ?Node
    {
        return new Name('array');
    }

    /**
     * @param ArrayType $type
     */
    public function mapToDocString(Type $type, ?Type $parentType = null): string
    {
        $itemType = $type->getItemType();
        $normalizedType = $this->typeNormalizer->normalizeArrayOfUnionToUnionArray($type);
        if ($normalizedType instanceof UnionType) {
            return $this->mapArrayUnionTypeToDocString($type, $normalizedType);
        }
        return $this->phpStanStaticTypeMapper->mapToDocString($itemType, $parentType) . '[]';
    }

    private function createArrayTypeNodeFromUnionType(UnionType $unionType): ArrayTypeNode
    {
        $unionedArrayType = [];
        foreach ($unionType->getTypes() as $unionedType) {
            $typeNode = $this->phpStanStaticTypeMapper->mapToPHPStanPhpDocTypeNode($unionedType);
            $unionedArrayType[(string) $typeNode] = $typeNode;
        }
        if (count($unionedArrayType) > 1) {
            return new AttributeAwareArrayTypeNode(new AttributeAwareUnionTypeNode($unionedArrayType));
        }
        /** @var TypeNode $arrayType */
        $arrayType = array_shift($unionedArrayType);
        return new AttributeAwareArrayTypeNode($arrayType);
    }

    private function isGenericArrayCandidate(ArrayType $arrayType): bool
    {
        if ($arrayType->getKeyType() instanceof MixedType) {
            return false;
        }
        // skip simple arrays, like "string[]", from converting to obvious "array<int, string>"
        if ($this->isIntegerKeyAndNonNestedArray($arrayType)) {
            return false;
        }
        if ($arrayType->getKeyType() instanceof NeverType) {
            return false;
        }
        // make sure the integer key type is not natural/implicit array int keys
        $keysArrayType = $arrayType->getKeysArray();
        if (! $keysArrayType instanceof ConstantArrayType) {
            return true;
        }
        foreach ($keysArrayType->getValueTypes() as $key => $keyType) {
            if (! $keyType instanceof ConstantIntegerType) {
                return true;
            }

            if ($key !== $keyType->getValue()) {
                return true;
            }
        }
        return false;
    }

    private function createGenericArrayType(ArrayType $arrayType, bool $withKey = false): AttributeAwareGenericTypeNode
    {
        $itemTypeNode = $this->phpStanStaticTypeMapper->mapToPHPStanPhpDocTypeNode($arrayType->getItemType());
        $attributeAwareIdentifierTypeNode = new AttributeAwareIdentifierTypeNode('array');
        if ($withKey) {
            $keyTypeNode = $this->phpStanStaticTypeMapper->mapToPHPStanPhpDocTypeNode($arrayType->getKeyType());
            $genericTypes = [$keyTypeNode, $itemTypeNode];
        } else {
            $genericTypes = [$itemTypeNode];
        }
        // @see https://github.com/phpstan/phpdoc-parser/blob/98a088b17966bdf6ee25c8a4b634df313d8aa531/tests/PHPStan/Parser/PhpDocParserTest.php#L2692-L2696
        foreach ($genericTypes as $genericType) {
            /** @var AttributeAwareNodeInterface $genericType */
            $genericType->setAttribute(self::HAS_GENERIC_TYPE_PARENT, $withKey);
        }
        $attributeAwareIdentifierTypeNode->setAttribute(self::HAS_GENERIC_TYPE_PARENT, $withKey);
        return new AttributeAwareGenericTypeNode($attributeAwareIdentifierTypeNode, $genericTypes);
    }

    private function mapArrayUnionTypeToDocString(ArrayType $arrayType, UnionType $unionType): string
    {
        $unionedTypesAsString = [];
        foreach ($unionType->getTypes() as $unionedArrayItemType) {
            $unionedTypesAsString[] = $this->phpStanStaticTypeMapper->mapToDocString($unionedArrayItemType, $arrayType);
        }
        $unionedTypesAsString = array_values($unionedTypesAsString);
        $unionedTypesAsString = array_unique($unionedTypesAsString);
        return implode('|', $unionedTypesAsString);
    }

    private function isIntegerKeyAndNonNestedArray(ArrayType $arrayType): bool
    {
        if (! $arrayType->getKeyType() instanceof IntegerType) {
            return false;
        }
        return ! $arrayType->getItemType() instanceof ArrayType;
    }

    private function narrowConstantArrayTypeOfUnionType(ArrayType $arrayType, Type $itemType): ?TypeNode
    {
        if ($arrayType instanceof ConstantArrayType && $itemType instanceof UnionType) {
            $narrowedItemType = $this->unionTypeCommonTypeNarrower->narrowToSharedObjectType($itemType);
            if ($narrowedItemType instanceof ObjectType) {
                $itemTypeNode = $this->phpStanStaticTypeMapper->mapToPHPStanPhpDocTypeNode($narrowedItemType);
                return new AttributeAwareArrayTypeNode($itemTypeNode);
            }

            $narrowedItemType = $this->unionTypeCommonTypeNarrower->narrowToGenericClassStringType($itemType);
            if ($narrowedItemType instanceof GenericClassStringType) {
                return $this->createTypeNodeFromGenericClassStringType($narrowedItemType);
            }
        }
        return null;
    }

    private function createTypeNodeFromGenericClassStringType(GenericClassStringType $genericClassStringType): \Rector\BetterPhpDocParser\Contract\PhpDocNode\AttributeAwareNodeInterface
    {
        $genericType = $genericClassStringType->getGenericType();
        if ($genericType instanceof ObjectType && ! $this->classLikeExistenceChecker->doesClassLikeExist($genericType->getClassName())) {
            return new AttributeAwareIdentifierTypeNode($genericType->getClassName());
        }
        $itemTypeNode = $this->phpStanStaticTypeMapper->mapToPHPStanPhpDocTypeNode($genericClassStringType);
        return new AttributeAwareGenericTypeNode(new AttributeAwareIdentifierTypeNode('array'), [$itemTypeNode]);
    }
}
