<?php

declare(strict_types=1);

namespace Rector\RemovingStatic\Tests\Rector\StaticPropertyFetch\DesiredStaticPropertyFetchTypeToDynamicRector;

use Iterator;
use Rector\Testing\PHPUnit\AbstractRectorTestCase;
use Symplify\SmartFileSystem\SmartFileInfo;

final class DesiredStaticPropertyFetchTypeToDynamicRectorTest extends AbstractRectorTestCase
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

    protected function provideConfigFileInfo(): ?SmartFileInfo
    {
        return new SmartFileInfo(__DIR__ . '/config/some_config.php');
    }
}
