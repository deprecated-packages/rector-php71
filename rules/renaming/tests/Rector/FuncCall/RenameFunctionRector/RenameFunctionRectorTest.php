<?php

declare(strict_types=1);

namespace Rector\Renaming\Tests\Rector\FuncCall\RenameFunctionRector;

use Iterator;
use Rector\Renaming\Rector\FuncCall\RenameFunctionRector;
use Rector\Testing\PHPUnit\AbstractRectorTestCase;
use Symplify\SmartFileSystem\SmartFileInfo;

final class RenameFunctionRectorTest extends AbstractRectorTestCase
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
            RenameFunctionRector::class => [
                RenameFunctionRector::OLD_FUNCTION_TO_NEW_FUNCTION => [
                    'view' => 'Laravel\Templating\render',
                    'sprintf' => 'Safe\sprintf',
                ],
            ],
        ];
    }
}
