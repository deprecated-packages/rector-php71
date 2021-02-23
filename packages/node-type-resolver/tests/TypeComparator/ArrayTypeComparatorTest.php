<?php

declare(strict_types=1);

namespace Rector\NodeTypeResolver\Tests\TypeComparator;

use Iterator;
use PHPStan\Type\ArrayType;
use PHPStan\Type\ClassStringType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Generic\GenericClassStringType;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StringType;
use Rector\Core\HttpKernel\RectorKernel;
use Rector\NodeTypeResolver\Tests\TypeComparator\Source\SomeGenericTypeObject;
use Rector\NodeTypeResolver\TypeComparator\ArrayTypeComparator;
use Rector\StaticTypeMapper\TypeFactory\UnionTypeFactory;
use Symplify\PackageBuilder\Testing\AbstractKernelTestCase;

final class ArrayTypeComparatorTest extends AbstractKernelTestCase
{
    /**
     * @var ArrayTypeComparator
     */
    private $arrayTypeComparator;

    protected function setUp(): void
    {
        $this->bootKernel(RectorKernel::class);
        $this->arrayTypeComparator = $this->getService(ArrayTypeComparator::class);
    }

    /**
     * @dataProvider provideData()
     */
    public function test(ArrayType $firstArrayType, ArrayType $secondArrayType, bool $areExpectedEqual): void
    {
        $areEqual = $this->arrayTypeComparator->isSubtype($firstArrayType, $secondArrayType);
        $this->assertSame($areExpectedEqual, $areEqual);
    }

    public function provideData(): Iterator
    {
        $unionTypeFactory = new UnionTypeFactory();

        $classStringKeysArrayType = new ArrayType(new StringType(), new ClassStringType());
        $stringArrayType = new ArrayType(new StringType(), new MixedType());
        yield [$stringArrayType, $classStringKeysArrayType, false];

        $genericClassStringType = new GenericClassStringType(new ObjectType(SomeGenericTypeObject::class));
        $constantArrayType = new ConstantArrayType(
            [new ConstantIntegerType(0)],
            [$unionTypeFactory->createUnionObjectType([$genericClassStringType, $genericClassStringType])]
        );

        yield [$constantArrayType, $stringArrayType, false];
    }
}
