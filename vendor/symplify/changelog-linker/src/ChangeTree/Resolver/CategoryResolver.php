<?php

declare(strict_types=1);

namespace Symplify\ChangelogLinker\ChangeTree\Resolver;

use Nette\Utils\Strings;
use Symplify\ChangelogLinker\ValueObject\Category;

/**
 * @see \Symplify\ChangelogLinker\Tests\ChangeTree\ChangeFactory\Resolver\CategoryResolverTest
 */
final class CategoryResolver
{
    /**
     * @var string
     * @see https://regex101.com/r/Tvajsb/1
     */
    private const ADDED_REGEX = '#\b(add(s|ed|ing)?)\b#i';

    /**
     * @var string
     * @see https://regex101.com/r/Y9y951/1
     */
    private const FIXED_REGEX = '#\b(fix(es|ed|ing)?)\b#i';

    /**
     * @var string
     * @see https://regex101.com/r/a23Pob/1
     */
    private const REMOVED_REGEX = '#\b(remov(e|es|ed|ing)|delet(e|es|ed|ing|)|drop(s|ped|ping)?)\b#i';

    /**
     * @var string
     * @see https://regex101.com/r/PQ1ahG/1
     */
    private const DEPRECATED_REGEX = '#\b(deprecat(e|es|ed|ing))\b#i';

    public function resolveCategory(string $message): string
    {
        if (Strings::match($message, self::ADDED_REGEX)) {
            return Category::ADDED;
        }

        if (Strings::match($message, self::FIXED_REGEX)) {
            return Category::FIXED;
        }

        if (Strings::match($message, self::DEPRECATED_REGEX)) {
            return Category::DEPRECATED;
        }

        if (Strings::match($message, self::REMOVED_REGEX)) {
            return Category::REMOVED;
        }

        return Category::CHANGED;
    }
}
