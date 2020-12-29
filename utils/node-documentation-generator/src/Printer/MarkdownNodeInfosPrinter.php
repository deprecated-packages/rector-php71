<?php

declare(strict_types=1);

namespace Rector\Utils\NodeDocumentationGenerator\Printer;

use Rector\Utils\NodeDocumentationGenerator\ValueObject\NodeCodeSample;
use Rector\Utils\NodeDocumentationGenerator\ValueObject\NodeInfo;

final class MarkdownNodeInfosPrinter
{
    /**
     * @param NodeInfo[] $nodeInfos
     */
    public function print(array $nodeInfos): string
    {
        $contentLines = [];
        $contentLines[] = '# Node Overview';
        $contentLines[] = 'Here you can find overview of commonly used nodes and how to build PHP code from them. For all nodes, [check php-parser code](https://github.com/nikic/PHP-Parser/tree/master/lib/PhpParser/Node).';
        foreach ($nodeInfos as $nodeInfo) {
            $contentLines[] = sprintf('## `%s`', $nodeInfo->getClass());

            $contentLines[] = $this->printCodeExample($nodeInfo);
            $contentLines[] = $this->printPublicProperties($nodeInfo);

            $contentLines[] = '<br>';
        }
        return $this->implodeLinesWithSpace($contentLines);
    }

    private function printCodeExample(NodeInfo $nodeInfo): string
    {
        $contentLines = [];
        $contentLines[] = '### Example PHP Code';
        foreach ($nodeInfo->getNodeCodeSamples() as $nodeCodeSample) {
            $contentLines[] = $this->printNodeCodeSample($nodeCodeSample);
            $contentLines[] = '<br>';
        }
        return $this->implodeLinesWithSpace($contentLines);
    }

    private function printPublicProperties(NodeInfo $nodeInfo): string
    {
        if (! $nodeInfo->hasPublicProperties()) {
            return '';
        }
        $contentLines = [];
        $contentLines[] = '### Public Properties';
        $contentLines[] = $this->implodeLines($nodeInfo->getPublicPropertyInfos());
        return $this->implodeLinesWithSpace($contentLines);
    }

    /**
     * @param string[] $contentLines
     */
    private function implodeLinesWithSpace(array $contentLines): string
    {
        return implode(PHP_EOL . PHP_EOL, $contentLines);
    }

    private function printNodeCodeSample(NodeCodeSample $nodeCodeSample): string
    {
        $contentLines = [
            $this->printPhpSnippet($nodeCodeSample->getPhpCode()),
            '↓',
            $this->printPhpSnippet($nodeCodeSample->getPrintedContent()),
        ];
        return $this->implodeLinesWithSpace($contentLines);
    }

    /**
     * @param string[] $contentLines
     */
    private function implodeLines(array $contentLines): string
    {
        return implode(PHP_EOL, $contentLines);
    }

    private function printPhpSnippet(string $printedContent): string
    {
        return sprintf('```php%s%s%s```', PHP_EOL, $printedContent, PHP_EOL);
    }
}
