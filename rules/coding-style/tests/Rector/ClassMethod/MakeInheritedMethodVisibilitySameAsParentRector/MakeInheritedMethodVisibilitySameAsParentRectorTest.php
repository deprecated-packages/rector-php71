<?php

declare(strict_types=1);

namespace Rector\CodingStyle\Tests\Rector\ClassMethod\MakeInheritedMethodVisibilitySameAsParentRector;

use Iterator;
use Rector\CodingStyle\Rector\ClassMethod\MakeInheritedMethodVisibilitySameAsParentRector;
use Rector\Testing\PHPUnit\AbstractRectorTestCase;
use Symplify\SmartFileSystem\SmartFileInfo;

final class MakeInheritedMethodVisibilitySameAsParentRectorTest extends AbstractRectorTestCase
{
    /**
     * @dataProvider provideData()
     */
    public function test(SmartFileInfo $fileInfo): void
    {
        $this->doTestFileInfo($fileInfo);
    }

    public function provideData(): Iterator
    {
        return $this->yieldFilesFromDirectory(__DIR__ . '/Fixture');
    }

    protected function getRectorClass(): string
    {
        return MakeInheritedMethodVisibilitySameAsParentRector::class;
    }
}
