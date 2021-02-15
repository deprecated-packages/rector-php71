<?php

declare(strict_types=1);

namespace Symplify\PHPStanRules\ValueObject;

final class Regex
{
    /**
     * @see https://regex101.com/r/6pPP8u/1
     * @var string
     */
    public const TESTS_PART_REGEX = '#(\\\\Tests\\\\|\\\\Tests$)#';

    /**
     * @see https://regex101.com/r/zyZ9KJ/1
     * @var string
     */
    public const VALUE_OBJECT_REGEX = '#\bValueObject\b#';
}
