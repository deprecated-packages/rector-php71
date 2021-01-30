<?php

declare(strict_types=1);

namespace Rector\Core\Rector\AbstractRector;

use Nette\Utils\Strings;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use Rector\ChangesReporting\Rector\AbstractRector\NotifyingRemovingNodeTrait;
use Rector\PostRector\Rector\AbstractRector\NodeCommandersTrait;

trait AbstractRectorTrait
{
    use RemovedAndAddedFilesTrait;
    use NodeTypeResolverTrait;
    use NameResolverTrait;
    use ConstFetchAnalyzerTrait;
    use BetterStandardPrinterTrait;
    use NodeCommandersTrait;
    use ValueResolverTrait;
    use SimpleCallableNodeTraverserTrait;
    use ComplexRemovalTrait;
    use NodeCollectorTrait;
    use NotifyingRemovingNodeTrait;

    protected function isNonAnonymousClass(Node $node): bool
    {
        if (! $node instanceof Class_) {
            return false;
        }
        $name = $this->getName($node);
        if ($name === null) {
            return false;
        }
        return ! Strings::contains($name, 'AnonymousClass');
    }

    protected function removeFinal(Class_ $class): void
    {
        $class->flags -= Class_::MODIFIER_FINAL;
    }
}
