<?php

declare(strict_types=1);

namespace Rector\Nette\Tests\Rector\MethodCall\ContextGetByTypeToConstructorInjectionRector;

use Iterator;
use Rector\Core\ValueObject\PhpVersionFeature;
use Rector\Nette\Rector\MethodCall\ContextGetByTypeToConstructorInjectionRector;
use Rector\Testing\PHPUnit\AbstractRectorTestCase;
use Symplify\SmartFileSystem\SmartFileInfo;

final class ContextGetByTypeToConstructorInjectionRectorTest extends AbstractRectorTestCase
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

    protected function getPhpVersion(): int
    {
        return PhpVersionFeature::TYPED_PROPERTIES - 1;
    }

    protected function getRectorClass(): string
    {
        return ContextGetByTypeToConstructorInjectionRector::class;
    }
}
