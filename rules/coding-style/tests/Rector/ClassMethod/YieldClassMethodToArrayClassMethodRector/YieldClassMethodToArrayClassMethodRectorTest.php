<?php

declare(strict_types=1);

namespace Rector\CodingStyle\Tests\Rector\ClassMethod\YieldClassMethodToArrayClassMethodRector;

use Iterator;
use Rector\CodingStyle\Rector\ClassMethod\YieldClassMethodToArrayClassMethodRector;
use Rector\CodingStyle\Tests\Rector\ClassMethod\YieldClassMethodToArrayClassMethodRector\Source\EventSubscriberInterface;
use Rector\Testing\PHPUnit\AbstractRectorTestCase;
use Symplify\SmartFileSystem\SmartFileInfo;

final class YieldClassMethodToArrayClassMethodRectorTest extends AbstractRectorTestCase
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
            YieldClassMethodToArrayClassMethodRector::class => [
                YieldClassMethodToArrayClassMethodRector::METHODS_BY_TYPE => [
                    EventSubscriberInterface::class => ['getSubscribedEvents'],
                ],
            ],
        ];
    }
}
