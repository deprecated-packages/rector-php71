<?php

declare(strict_types=1);

namespace Rector\PostRector\Rector;

use PhpParser\Node;
use PhpParser\Node\Name;
use Rector\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory;
use Rector\CodingStyle\ClassNameImport\ClassNameImportSkipper;
use Rector\CodingStyle\Node\NameImporter;
use Rector\Core\Configuration\Option;
use Rector\NodeTypeResolver\PhpDoc\NodeAnalyzer\DocBlockNameImporter;
use Symplify\PackageBuilder\Parameter\ParameterProvider;

final class NameImportingPostRector extends AbstractPostRector
{
    /**
     * @var ParameterProvider
     */
    private $parameterProvider;

    /**
     * @var NameImporter
     */
    private $nameImporter;

    /**
     * @var DocBlockNameImporter
     */
    private $docBlockNameImporter;

    /**
     * @var ClassNameImportSkipper
     */
    private $classNameImportSkipper;

    /**
     * @var PhpDocInfoFactory
     */
    private $phpDocInfoFactory;

    public function __construct(ParameterProvider $parameterProvider, NameImporter $nameImporter, DocBlockNameImporter $docBlockNameImporter, ClassNameImportSkipper $classNameImportSkipper, PhpDocInfoFactory $phpDocInfoFactory)
    {
        $this->parameterProvider = $parameterProvider;
        $this->nameImporter = $nameImporter;
        $this->docBlockNameImporter = $docBlockNameImporter;
        $this->classNameImportSkipper = $classNameImportSkipper;
        $this->phpDocInfoFactory = $phpDocInfoFactory;
    }

    public function enterNode(Node $node): ?Node
    {
        $autoImportNames = $this->parameterProvider->provideParameter(Option::AUTO_IMPORT_NAMES);
        if (! $autoImportNames) {
            return null;
        }
        if ($node instanceof Name) {
            return $this->processNodeName($node);
        }
        $importDocBlocks = (bool) $this->parameterProvider->provideParameter(Option::IMPORT_DOC_BLOCKS);
        if (! $importDocBlocks) {
            return null;
        }
        $phpDocInfo = $this->phpDocInfoFactory->createFromNodeOrEmpty($node);
        $this->docBlockNameImporter->importNames($phpDocInfo, $node);
        return $node;
    }

    public function getPriority(): int
    {
        return 600;
    }

    private function processNodeName(Name $name): ?Node
    {
        if ($name->isSpecialClassName()) {
            return $name;
        }
        $importName = $this->getName($name);
        if (! is_callable($importName)) {
            return $this->nameImporter->importName($name);
        }
        if (substr_count($name->toCodeString(), '\\') > 1
            && $this->classNameImportSkipper->isFoundInUse($name)
            && ! function_exists($name->getLast())) {
            return null;
        }
        return $this->nameImporter->importName($name);
    }
}
