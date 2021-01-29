<?php

declare(strict_types=1);

namespace Rector\Visibility\Tests\Rector\ClassMethod\ChangeMethodVisibilityRector;

use Iterator;
use Rector\Core\ValueObject\Visibility;
use Rector\Testing\PHPUnit\AbstractRectorTestCase;
use Rector\Visibility\Rector\ClassMethod\ChangeMethodVisibilityRector;
use Rector\Visibility\Tests\Rector\ClassMethod\ChangeMethodVisibilityRector\Source\ParentObject;
use Rector\Visibility\ValueObject\ChangeMethodVisibility;
use Symplify\SmartFileSystem\SmartFileInfo;

final class ChangeMethodVisibilityRectorTest extends AbstractRectorTestCase
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
            ChangeMethodVisibilityRector::class => [
                ChangeMethodVisibilityRector::METHOD_VISIBILITIES => [
                    new ChangeMethodVisibility(ParentObject::class, 'toBePublicMethod', Visibility::PUBLIC),
                    new ChangeMethodVisibility(ParentObject::class, 'toBeProtectedMethod', Visibility::PROTECTED),
                    new ChangeMethodVisibility(ParentObject::class, 'toBePrivateMethod', Visibility::PRIVATE),
                    new ChangeMethodVisibility(ParentObject::class, 'toBePublicStaticMethod', Visibility::PUBLIC),
                ],
            ],
        ];
    }
}
