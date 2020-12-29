<?php

declare(strict_types=1);

namespace Rector\Testing\Application;

use Rector\Core\Configuration\RenamedClassesDataCollector;
use Rector\Core\Contract\Rector\RectorInterface;
use Rector\Renaming\Rector\Name\RenameClassRector;
use Rector\Testing\PHPUnit\StaticPHPUnitEnvironment;

final class EnabledRectorsProvider
{
    /**
     * @var mixed[][]
     */
    private $enabledRectorsWithConfiguration = [];

    /**
     * @var RenamedClassesDataCollector
     */
    private $renamedClassesDataCollector;

    public function __construct(RenamedClassesDataCollector $renamedClassesDataCollector)
    {
        $this->renamedClassesDataCollector = $renamedClassesDataCollector;
    }

    /**
     * @param mixed[] $configuration
     */
    public function addEnabledRector(string $rector, array $configuration = []): void
    {
        $this->enabledRectorsWithConfiguration[$rector] = $configuration;
        if (! StaticPHPUnitEnvironment::isPHPUnitRun()) {
            return;
        }
        if (! is_a($rector, RenameClassRector::class, true)) {
            return;
        }
        // only in unit tests
        $this->renamedClassesDataCollector->setOldToNewClasses($configuration[RenameClassRector::OLD_TO_NEW_CLASSES] ?? []);
    }

    public function reset(): void
    {
        $this->enabledRectorsWithConfiguration = [];
    }

    public function isConfigured(): bool
    {
        return (bool) $this->enabledRectorsWithConfiguration;
    }

    /**
     * @return mixed[][]
     */
    public function getEnabledRectors(): array
    {
        return $this->enabledRectorsWithConfiguration;
    }

    /**
     * @return mixed[]
     */
    public function getRectorConfiguration(RectorInterface $rector): array
    {
        foreach ($this->enabledRectorsWithConfiguration as $rectorClass => $configuration) {
            if (! is_a($rector, $rectorClass, true)) {
                continue;
            }

            return $configuration;
        }
        return [];
    }
}
