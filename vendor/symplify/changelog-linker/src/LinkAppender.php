<?php

declare(strict_types=1);

namespace Symplify\ChangelogLinker;

use Symplify\ChangelogLinker\Analyzer\LinksAnalyzer;

final class LinkAppender
{
    /**
     * @var bool
     */
    private $isExistingLinks = false;

    /**
     * @var string[]
     */
    private $linksToAppend = [];

    /**
     * @var LinksAnalyzer
     */
    private $linksAnalyzer;

    public function __construct(LinksAnalyzer $linksAnalyzer)
    {
        $this->linksAnalyzer = $linksAnalyzer;
    }

    public function hasId(string $id): bool
    {
        return array_key_exists($id, $this->linksToAppend);
    }

    public function add(string $id, string $link): void
    {
        $this->linksToAppend[$id] = $link;
    }

    /**
     * @return string[]
     */
    public function getLinksToAppend(): array
    {
        krsort($this->linksToAppend);

        // filter out already existing links
        $this->removeAlreadyExistingLinks();

        return $this->linksToAppend;
    }

    /**
     * Tells you if links have been removed from LinkAppender::$linksToAppend
     * after calling LinkAppender::removeAlreadyExistingLinks
     *
     * Implicitly this method is telling you that changelog file already
     * contains links at the end.
     */
    public function isExistingLinks(): bool
    {
        return $this->isExistingLinks;
    }

    private function removeAlreadyExistingLinks(): void
    {
        $this->isExistingLinks = false;

        $ids = array_keys($this->linksToAppend);
        foreach ($ids as $id) {
            if ($this->linksAnalyzer->hasLinkedId((string) $id)) {
                unset($this->linksToAppend[$id]);
                $this->isExistingLinks = true;
            }
        }
    }
}
