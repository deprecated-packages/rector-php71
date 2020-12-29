<?php

declare(strict_types=1);

namespace Rector\Generic\Tests\Rector\ClassMethod\ArgumentRemoverRector;

use Iterator;
use Rector\Generic\Rector\ClassMethod\ArgumentRemoverRector;
use Rector\Generic\Tests\Rector\ClassMethod\ArgumentRemoverRector\Source\Persister;
use Rector\Generic\Tests\Rector\ClassMethod\ArgumentRemoverRector\Source\RemoveInTheMiddle;
use Rector\Generic\ValueObject\ArgumentRemover;
use Rector\Testing\PHPUnit\AbstractRectorTestCase;
use Symfony\Component\Yaml\Yaml;
use Symplify\SmartFileSystem\SmartFileInfo;

final class ArgumentRemoverRectorTest extends AbstractRectorTestCase
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
            ArgumentRemoverRector::class => [
                ArgumentRemoverRector::REMOVED_ARGUMENTS => [
                    new ArgumentRemover(Persister::class, 'getSelectJoinColumnSQL', 4, null),
                    new ArgumentRemover(Yaml::class, 'parse', 1, [
                        'Symfony\Component\Yaml\Yaml::PARSE_KEYS_AS_STRINGS',
                        'hey',
                        55,
                        5.5,
                    ]),
                    new ArgumentRemover(RemoveInTheMiddle::class, 'run', 1, [
                        'name' => 'second',
                    ]),
                ],
            ],
        ];
    }
}
