<?php

declare(strict_types=1);

namespace Symplify\PHPStanRules\ObjectCalisthenics\Tests\Rules\SingleIndentationInMethodRule\Fixture;

final class SkipSingleIndentation
{
    public function someMethod()
    {
        if (true) {
            return 'maybe';
        }

        return 'sure';
    }
}
