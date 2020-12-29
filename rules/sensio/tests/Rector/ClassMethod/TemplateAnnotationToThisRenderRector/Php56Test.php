<?php

declare(strict_types=1);

namespace Rector\Sensio\Tests\Rector\ClassMethod\TemplateAnnotationToThisRenderRector;

use Iterator;
use Rector\Core\ValueObject\PhpVersion;
use Rector\Sensio\Rector\ClassMethod\TemplateAnnotationToThisRenderRector;
use Rector\Testing\PHPUnit\AbstractRectorTestCase;
use Symplify\SmartFileSystem\SmartFileInfo;

final class Php56Test extends AbstractRectorTestCase
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
        return $this->yieldFilesFromDirectory(__DIR__ . '/FixturePhp56');
    }

    protected function getRectorClass(): string
    {
        return TemplateAnnotationToThisRenderRector::class;
    }

    protected function getPhpVersion(): int
    {
        return PhpVersion::PHP_56;
    }
}
