<?php

declare(strict_types=1);

namespace Symplify\ChangelogLinker\Analyzer;

use Nette\Utils\Strings;

/**
 * @see \Symplify\ChangelogLinker\Tests\Analyzer\IdsAnalyzer\IdsAnalyzerTest
 */
final class IdsAnalyzer
{
    /**
     * @var string
     * @see https://regex101.com/r/HjmbHg/1
     *
     * Covers cases like:
     * - #5 Add this => 5
     * - [#10] Change that => 10
     */
    private const PR_REFERENCE_IN_LIST_REGEX = '#- \[?(\#(?<id>\d+))\]?#';

    public function getHighestIdInChangelog(string $content): int
    {
        $ids = $this->getAllIdsInChangelog($content);
        if ($ids === null) {
            return 0;
        }
        if ($ids === []) {
            return 0;
        }

        return (int) max($ids);
    }

    public function getAllIdsInChangelog(string $content): ?array
    {
        $matches = Strings::matchAll($content, self::PR_REFERENCE_IN_LIST_REGEX);
        if ($matches === []) {
            return null;
        }
        return array_column($matches, 'id');
    }
}
