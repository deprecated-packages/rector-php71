<?php

declare(strict_types=1);

namespace Rector\NodeTypeResolver\Tests\PerNodeTypeResolver\PropertyFetchTypeResolver;

use Iterator;
use PhpParser\Node\Expr\PropertyFetch;
use PHPStan\Type\ArrayType;
use PHPStan\Type\ErrorType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
use Rector\NodeTypeResolver\Tests\PerNodeTypeResolver\PropertyFetchTypeResolver\Source\Abc;

/**
 * @see \Rector\NodeTypeResolver\NodeTypeResolver\PropertyFetchTypeResolver
 */
final class PropertyFetchTypeResolverTest extends AbstractPropertyFetchTypeResolverTest
{
    /**
     * @dataProvider provideData()
     */
    public function test(string $file, int $nodePosition, Type $expectedType): void
    {
        $propertyFetchNodes = $this->getNodesForFileOfType($file, PropertyFetch::class);

        $resolvedType = $this->nodeTypeResolver->resolve($propertyFetchNodes[$nodePosition]);

        $expectedTypeClass = get_class($expectedType);
        $this->assertInstanceOf($expectedTypeClass, $resolvedType);

        $expectedTypeAsString = $this->getStringFromType($expectedType);
        $resolvedTypeAsString = $this->getStringFromType($resolvedType);

        $this->assertEquals($expectedTypeAsString, $resolvedTypeAsString);
    }

    public function provideData(): Iterator
    {
        foreach ([
            __DIR__ . '/Source/phpDocPropertyFetchOnTypedVar.php',
            __DIR__ . '/Source/phpDocPropertyFetchOnVarInScope.php',
        ] as $file) {
            yield [$file, 0, new StringType()];
            yield [$file, 1, new IntegerType()];
            yield [$file, 2, new UnionType([new StringType(), new NullType()])];
            yield [$file, 3, new UnionType([new IntegerType(), new NullType()])];
            yield [$file, 4, new ObjectType(Abc::class)];
            yield [$file, 5, new UnionType([new ObjectType(Abc::class), new NullType()])];
            yield [$file, 6, new ObjectType(Abc::class)];
            yield [
                $file,
                7,
                new ObjectType(
                    \Rector\NodeTypeResolver\Tests\PerNodeTypeResolver\PropertyFetchTypeResolver\Source\IDontExist::class
                ),
            ];
            yield [$file, 8, new ObjectType(\A\B\C\IDontExist::class)];
            yield [$file, 9, new ArrayType(new MixedType(), new MixedType())];
            yield [$file, 10, new ArrayType(new MixedType(), new ObjectType(Abc::class))];
            yield [$file, 11, new MixedType()];
            yield [$file, 12, new MixedType()];
            yield [$file, 13, new ErrorType()];
        }

        yield [__DIR__ . '/Source/propertyFetchOnMixedVar.php', 0, new MixedType()];
        yield [__DIR__ . '/Source/propertyFetchOnMixedVar.php', 1, new MixedType()];
        yield [__DIR__ . '/Source/propertyFetchOnMixedVar.php', 2, new MixedType()];
    }
}
