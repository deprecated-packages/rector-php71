<?php

declare(strict_types=1);

namespace Rector\Core\PhpParser\NodeTraverser;

use PhpParser\Node;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\NodeFinder;
use PhpParser\NodeTraverser;
use Rector\Caching\Contract\Rector\ZeroCacheRectorInterface;
use Rector\Core\Application\ActiveRectorsProvider;
use Rector\Core\Configuration\Configuration;
use Rector\Core\Contract\Rector\PhpRectorInterface;
use Rector\Core\PhpParser\Node\CustomNode\FileNode;
use Rector\Core\PhpParser\Node\CustomNode\FileWithoutNamespace;
use Rector\NodeTypeResolver\FileSystem\CurrentFileInfoProvider;
use Rector\NodeTypeResolver\Node\AttributeKey;
use Rector\Testing\Application\EnabledRectorProvider;

final class RectorNodeTraverser extends NodeTraverser
{
    /**
     * @var PhpRectorInterface[]
     */
    private $allPhpRectors = [];

    /**
     * @var EnabledRectorProvider
     */
    private $enabledRectorProvider;

    /**
     * @var NodeFinder
     */
    private $nodeFinder;

    /**
     * @var CurrentFileInfoProvider
     */
    private $currentFileInfoProvider;

    public function __construct(EnabledRectorProvider $enabledRectorProvider, Configuration $configuration, ActiveRectorsProvider $activeRectorsProvider, NodeFinder $nodeFinder, CurrentFileInfoProvider $currentFileInfoProvider)
    {
        /** @var PhpRectorInterface[] $phpRectors */
        $phpRectors = $activeRectorsProvider->provideByType(PhpRectorInterface::class);
        $this->allPhpRectors = $phpRectors;
        $this->enabledRectorProvider = $enabledRectorProvider;
        foreach ($phpRectors as $phpRector) {
            if ($configuration->isCacheEnabled() && ! $configuration->shouldClearCache() && $phpRector instanceof ZeroCacheRectorInterface) {
                continue;
            }

            $this->addVisitor($phpRector);
        }
        $this->nodeFinder = $nodeFinder;
        $this->currentFileInfoProvider = $currentFileInfoProvider;
    }

    /**
     * @return Node[]
     */
    public function traverseFileNode(FileNode $fileNode): array
    {
        if ($this->enabledRectorProvider->isConfigured()) {
            $this->configureEnabledRectorOnly();
        }
        if (! $this->hasFileNodeRectorsEnabled()) {
            return [];
        }
        // here we only traverse file node without children, to prevent duplicatd traversion
        foreach ($this->visitors as $rector) {
            $rector->enterNode($fileNode);
        }
        return [];
    }

    /**
     * @param Node[] $nodes
     * @return Node[]
     */
    public function traverse(array $nodes): array
    {
        if ($this->enabledRectorProvider->isConfigured()) {
            $this->configureEnabledRectorOnly();
        }
        $hasNamespace = (bool) $this->nodeFinder->findFirstInstanceOf($nodes, Namespace_::class);
        if (! $hasNamespace && $nodes !== []) {
            $fileWithoutNamespace = new FileWithoutNamespace($nodes);
            $fileWithoutNamespace->setAttribute(AttributeKey::FILE_INFO, $this->currentFileInfoProvider->getSmartFileInfo());

            $firstNode = $nodes[0];
            $fileWithoutNamespace->setAttribute(AttributeKey::DECLARES, $firstNode->getAttribute(AttributeKey::DECLARES));

            return parent::traverse([$fileWithoutNamespace]);
        }
        return parent::traverse($nodes);
    }

    public function getPhpRectorCount(): int
    {
        return count($this->visitors);
    }

    public function hasZeroCacheRectors(): bool
    {
        return (bool) $this->getZeroCacheRectorCount();
    }

    public function getZeroCacheRectorCount(): int
    {
        $zeroCacheRectors = array_filter($this->allPhpRectors, function (PhpRectorInterface $phpRector) : bool {
            return $phpRector instanceof ZeroCacheRectorInterface;
        });

        return count($zeroCacheRectors);
    }

    /**
     * Mostly used for testing
     */
    private function configureEnabledRectorOnly(): void
    {
        $this->visitors = [];
        $enabledRector = $this->enabledRectorProvider->getEnabledRector();

        foreach ($this->allPhpRectors as $phpRector) {
            if (! is_a($phpRector, $enabledRector, true)) {
                continue;
            }

            $this->addVisitor($phpRector);
            break;
        }
    }

    private function hasFileNodeRectorsEnabled(): bool
    {
        foreach ($this->visitors as $visitor) {
            if (! $visitor instanceof PhpRectorInterface) {
                continue;
            }

            if (! in_array(FileNode::class, $visitor->getNodeTypes(), true)) {
                continue;
            }

            return true;
        }

        return false;
    }
}
