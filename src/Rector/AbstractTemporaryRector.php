<?php

declare(strict_types=1);

namespace Rector\Core\Rector;

use PhpParser\BuilderFactory;
use PhpParser\Comment;
use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\Cast\Bool_;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Return_;
use PhpParser\NodeVisitorAbstract;
use Rector\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory;
use Rector\Core\Configuration\CurrentNodeProvider;
use Rector\Core\Configuration\Option;
use Rector\Core\Contract\Rector\PhpRectorInterface;
use Rector\Core\Exclusion\ExclusionManager;
use Rector\Core\Logging\CurrentRectorProvider;
use Rector\Core\NodeAnalyzer\ClassAnalyzer;
use Rector\Core\NodeManipulator\VisibilityManipulator;
use Rector\Core\Php\PhpVersionProvider;
use Rector\Core\PhpParser\Node\BetterNodeFinder;
use Rector\Core\PhpParser\Node\NodeFactory;
use Rector\Core\PhpParser\Node\Value\ValueResolver;
use Rector\Core\Rector\AbstractRector\AbstractRectorTrait;
use Rector\Core\ValueObject\ProjectType;
use Rector\NodeCollector\NodeCollector\NodeRepository;
use Rector\NodeTypeResolver\Node\AttributeKey;
use Rector\StaticTypeMapper\StaticTypeMapper;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symplify\PackageBuilder\Parameter\ParameterProvider;
use Symplify\Skipper\Skipper\Skipper;
use Symplify\SmartFileSystem\SmartFileInfo;

abstract class AbstractTemporaryRector extends NodeVisitorAbstract implements PhpRectorInterface
{
    use AbstractRectorTrait;

    /**
     * @var string[]
     */
    private const ATTRIBUTES_TO_MIRROR = [
        AttributeKey::PARENT_NODE,
        AttributeKey::CLASS_NODE,
        AttributeKey::CLASS_NAME,
        AttributeKey::FILE_INFO,
        AttributeKey::METHOD_NODE,
        AttributeKey::USE_NODES,
        AttributeKey::SCOPE,
        AttributeKey::METHOD_NAME,
        AttributeKey::NAMESPACE_NAME,
        AttributeKey::NAMESPACE_NODE,
        AttributeKey::RESOLVED_NAME,
    ];

    /**
     * @var BuilderFactory
     */
    protected $builderFactory;

    /**
     * @var ParameterProvider
     */
    protected $parameterProvider;

    /**
     * @var PhpVersionProvider
     */
    protected $phpVersionProvider;

    /**
     * @var StaticTypeMapper
     */
    protected $staticTypeMapper;

    /**
     * @var PhpDocInfoFactory
     */
    protected $phpDocInfoFactory;

    /**
     * @var NodeFactory
     */
    protected $nodeFactory;

    /**
     * @var VisibilityManipulator
     */
    protected $visibilityManipulator;

    /**
     * @var ValueResolver
     */
    protected $valueResolver;

    /**
     * @var NodeRepository
     */
    protected $nodeRepository;

    /**
     * @var BetterNodeFinder
     */
    protected $betterNodeFinder;

    /**
     * @var ClassAnalyzer
     */
    protected $classNodeAnalyzer;

    /**
     * @var SymfonyStyle
     */
    private $symfonyStyle;

    /**
     * @var ExclusionManager
     */
    private $exclusionManager;

    /**
     * @var CurrentRectorProvider
     */
    private $currentRectorProvider;

    /**
     * @var CurrentNodeProvider
     */
    private $currentNodeProvider;

    /**
     * @var Skipper
     */
    private $skipper;

    /**
     * @var string|null
     */
    private $previousAppliedClass;

    /**
     * @required
     */
    public function autowireAbstractTemporaryRector(VisibilityManipulator $visibilityManipulator, NodeFactory $nodeFactory, PhpDocInfoFactory $phpDocInfoFactory, SymfonyStyle $symfonyStyle, PhpVersionProvider $phpVersionProvider, BuilderFactory $builderFactory, ExclusionManager $exclusionManager, StaticTypeMapper $staticTypeMapper, ParameterProvider $parameterProvider, CurrentRectorProvider $currentRectorProvider, ClassAnalyzer $classAnalyzer, CurrentNodeProvider $currentNodeProvider, Skipper $skipper, ValueResolver $valueResolver, NodeRepository $nodeRepository, BetterNodeFinder $betterNodeFinder): void
    {
        $this->visibilityManipulator = $visibilityManipulator;
        $this->nodeFactory = $nodeFactory;
        $this->phpDocInfoFactory = $phpDocInfoFactory;
        $this->symfonyStyle = $symfonyStyle;
        $this->phpVersionProvider = $phpVersionProvider;
        $this->builderFactory = $builderFactory;
        $this->exclusionManager = $exclusionManager;
        $this->staticTypeMapper = $staticTypeMapper;
        $this->parameterProvider = $parameterProvider;
        $this->currentRectorProvider = $currentRectorProvider;
        $this->classNodeAnalyzer = $classAnalyzer;
        $this->currentNodeProvider = $currentNodeProvider;
        $this->skipper = $skipper;
        $this->valueResolver = $valueResolver;
        $this->nodeRepository = $nodeRepository;
        $this->betterNodeFinder = $betterNodeFinder;
    }

    /**
     * @return Node[]|null
     */
    public function beforeTraverse(array $nodes): ?array
    {
        $this->previousAppliedClass = null;
        return parent::beforeTraverse($nodes);
    }

    /**
     * @return Expression|Node|null
     */
    public final function enterNode(Node $node)
    {
        $nodeClass = get_class($node);
        if (! $this->isMatchingNodeType($nodeClass)) {
            return null;
        }
        $this->currentRectorProvider->changeCurrentRector($this);
        // for PHP doc info factory and change notifier
        $this->currentNodeProvider->setNode($node);
        // already removed
        if ($this->shouldSkipCurrentNode($node)) {
            return null;
        }
        // show current Rector class on --debug
        $this->printDebugApplying();
        $originalNode = $node->getAttribute(AttributeKey::ORIGINAL_NODE) ?? clone $node;
        $originalNodeWithAttributes = clone $node;
        $node = $this->refactor($node);
        // nothing to change → continue
        if (! $node instanceof Node) {
            return null;
        }
        // changed!
        if ($this->hasNodeChanged($originalNode, $node)) {
            $this->mirrorAttributes($originalNodeWithAttributes, $node);
            $this->updateAttributes($node);
            $this->keepFileInfoAttribute($node, $originalNode);
            $this->notifyNodeFileInfo($node);
        }
        // if stmt ("$value;") was replaced by expr ("$value"), add the ending ";" (Expression) to prevent breaking the code
        if ($originalNode instanceof Stmt && $node instanceof Expr) {
            return new Expression($node);
        }
        return $node;
    }

    protected function getNextExpression(Node $node): ?Node
    {
        $currentExpression = $node->getAttribute(AttributeKey::CURRENT_STATEMENT);
        if (! $currentExpression instanceof Expression) {
            return null;
        }
        return $currentExpression->getAttribute(AttributeKey::NEXT_NODE);
    }

    /**
     * @param Expr[]|null[] $nodes
     * @param mixed[] $expectedValues
     */
    protected function areValues(array $nodes, array $expectedValues): bool
    {
        foreach ($nodes as $i => $node) {
            if ($node !== null && $this->valueResolver->isValue($node, $expectedValues[$i])) {
                continue;
            }

            return false;
        }
        return true;
    }

    protected function isAtLeastPhpVersion(int $version): bool
    {
        return $this->phpVersionProvider->isAtLeastPhpVersion($version);
    }

    protected function mirrorComments(Node $newNode, Node $oldNode): void
    {
        $newNode->setAttribute(AttributeKey::PHP_DOC_INFO, $oldNode->getAttribute(AttributeKey::PHP_DOC_INFO));
        $newNode->setAttribute(AttributeKey::COMMENTS, $oldNode->getAttribute(AttributeKey::COMMENTS));
    }

    protected function rollbackComments(Node $node, Comment $comment): void
    {
        $node->setAttribute(AttributeKey::COMMENTS, null);
        $node->setDocComment(new Doc($comment->getText()));
        $node->setAttribute(AttributeKey::PHP_DOC_INFO, null);
    }

    /**
     * @param Stmt[] $stmts
     */
    protected function unwrapStmts(array $stmts, Node $node): void
    {
        // move /* */ doc block from if to first element to keep it
        $currentPhpDocInfo = $this->phpDocInfoFactory->createFromNodeOrEmpty($node);
        foreach ($stmts as $key => $ifStmt) {
            if ($key === 0) {
                $ifStmt->setAttribute(AttributeKey::PHP_DOC_INFO, $currentPhpDocInfo);

                // move // comments
                $ifStmt->setAttribute(AttributeKey::COMMENTS, $node->getComments());
            }

            $this->addNodeAfterNode($ifStmt, $node);
        }
    }

    protected function isOnClassMethodCall(Node $node, string $type, string $methodName): bool
    {
        if (! $node instanceof MethodCall) {
            return false;
        }
        if (! $this->isObjectType($node->var, $type)) {
            return false;
        }
        return $this->isName($node->name, $methodName);
    }

    protected function isOpenSourceProjectType(): bool
    {
        $projectType = $this->parameterProvider->provideParameter(Option::PROJECT_TYPE);

        return in_array(
            $projectType,
            // make it typo proof
            [ProjectType::OPEN_SOURCE, ProjectType::OPEN_SOURCE_UNDESCORED],
            true
        );
    }

    /**
     * @param Expr $expr
     */
    protected function createBoolCast(?Node $parentNode, Node $expr): Bool_
    {
        if ($parentNode instanceof Return_ && $expr instanceof Assign) {
            $expr = $expr->expr;
        }
        return new Bool_($expr);
    }

    /**
     * @param Arg[] $newArgs
     * @param Arg[] $appendingArgs
     * @return Arg[]
     */
    protected function appendArgs(array $newArgs, array $appendingArgs): array
    {
        foreach ($appendingArgs as $oldArgument) {
            $newArgs[] = new Arg($oldArgument->value);
        }
        return $newArgs;
    }

    protected function unwrapExpression(Stmt $stmt): Node
    {
        if ($stmt instanceof Expression) {
            return $stmt->expr;
        }
        return $stmt;
    }

    private function isMatchingNodeType(string $nodeClass): bool
    {
        foreach ($this->getNodeTypes() as $nodeType) {
            if (is_a($nodeClass, $nodeType, true)) {
                return true;
            }
        }
        return false;
    }

    private function shouldSkipCurrentNode(Node $node): bool
    {
        if ($this->isNodeRemoved($node)) {
            return true;
        }
        if ($this->exclusionManager->isNodeSkippedByRector($node, $this)) {
            return true;
        }
        $fileInfo = $node->getAttribute(AttributeKey::FILE_INFO);
        if (! $fileInfo instanceof SmartFileInfo) {
            return false;
        }
        return $this->skipper->shouldSkipElementAndFileInfo($this, $fileInfo);
    }

    private function printDebugApplying(): void
    {
        if (! $this->symfonyStyle->isDebug()) {
            return;
        }

        if ($this->previousAppliedClass === static::class) {
            return;
        }

        // prevent spamming with the same class over and over
        // indented on purpose to improve log nesting under [refactoring]
        $this->symfonyStyle->writeln('    [applying] ' . static::class);
        $this->previousAppliedClass = static::class;
    }

    private function hasNodeChanged(Node $originalNode, Node $node): bool
    {
        if ($this->isNameIdentical($node, $originalNode)) {
            return false;
        }
        return ! $this->areNodesEqual($originalNode, $node);
    }

    private function mirrorAttributes(Node $oldNode, Node $newNode): void
    {
        foreach ($oldNode->getAttributes() as $attributeName => $oldNodeAttributeValue) {
            if (! in_array($attributeName, self::ATTRIBUTES_TO_MIRROR, true)) {
                continue;
            }

            $newNode->setAttribute($attributeName, $oldNodeAttributeValue);
        }
    }

    private function updateAttributes(Node $node): void
    {
        // update Resolved name attribute if name is changed
        if ($node instanceof Name) {
            $node->setAttribute(AttributeKey::RESOLVED_NAME, $node->toString());
        }
    }

    private function keepFileInfoAttribute(Node $node, Node $originalNode): void
    {
        $fileInfo = $node->getAttribute(AttributeKey::FILE_INFO);
        if ($fileInfo instanceof SmartFileInfo) {
            return;
        }
        $fileInfo = $originalNode->getAttribute(AttributeKey::FILE_INFO);
        $originalParent = $originalNode->getAttribute(AttributeKey::PARENT_NODE);
        if ($fileInfo !== null) {
            $node->setAttribute(AttributeKey::FILE_INFO, $originalNode->getAttribute(AttributeKey::FILE_INFO));
        } elseif ($originalParent instanceof Node) {
            $node->setAttribute(AttributeKey::FILE_INFO, $originalParent->getAttribute(AttributeKey::FILE_INFO));
        }
    }

    private function isNameIdentical(Node $node, Node $originalNode): bool
    {
        if (! $originalNode instanceof Name) {
            return false;
        }
        // names are the same
        return $this->areNodesEqual($originalNode->getAttribute(AttributeKey::ORIGINAL_NAME), $node);
    }
}
