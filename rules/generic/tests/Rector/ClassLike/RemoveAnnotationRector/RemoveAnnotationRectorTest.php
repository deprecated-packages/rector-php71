<?php

declare(strict_types=1);

namespace Rector\Generic\Tests\Rector\ClassLike\RemoveAnnotationRector;

use Iterator;
use Rector\Generic\Rector\ClassLike\RemoveAnnotationRector;
use Rector\Testing\PHPUnit\AbstractRectorTestCase;
use Symplify\SmartFileSystem\SmartFileInfo;

final class RemoveAnnotationRectorTest extends AbstractRectorTestCase
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

    /**
     * @return array<string, mixed[]>
     */
    protected function getRectorsWithConfiguration(): array
    {
        return [
            RemoveAnnotationRector::class => [
                RemoveAnnotationRector::ANNOTATIONS_TO_REMOVE => ['method'],
            ],
        ];
    }
}
