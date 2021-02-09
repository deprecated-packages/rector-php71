<?php

declare(strict_types=1);

namespace Rector\NetteToSymfony\Rector\Class_;

use Nette\Utils\Strings;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Return_;
use Rector\Core\Exception\ShouldNotHappenException;
use Rector\Core\Rector\AbstractRector;
use Rector\Core\ValueObject\PhpVersionFeature;
use Rector\Nette\NodeFactory\ActionRenderFactory;
use Rector\Nette\TemplatePropertyAssignCollector;
use Rector\NodeTypeResolver\Node\AttributeKey;
use Rector\StaticTypeMapper\ValueObject\Type\FullyQualifiedObjectType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * @see https://doc.nette.org/en/2.4/components
 * ↓
 * @see https://symfony.com/doc/current/controller.html
 * @see \Rector\NetteToSymfony\Tests\Rector\Class_\NetteControlToSymfonyControllerRector\NetteControlToSymfonyControllerRectorTest
 */
final class NetteControlToSymfonyControllerRector extends AbstractRector
{
    /**
     * @var TemplatePropertyAssignCollector
     */
    private $templatePropertyAssignCollector;

    /**
     * @var ActionRenderFactory
     */
    private $actionRenderFactory;

    public function __construct(ActionRenderFactory $actionRenderFactory, TemplatePropertyAssignCollector $templatePropertyAssignCollector)
    {
        $this->templatePropertyAssignCollector = $templatePropertyAssignCollector;
        $this->actionRenderFactory = $actionRenderFactory;
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Migrate Nette Component to Symfony Controller', [
            new CodeSample(<<<'CODE_SAMPLE'
use Nette\Application\UI\Control;

class SomeControl extends Control
{
    public function render()
    {
        $this->template->param = 'some value';
        $this->template->render(__DIR__ . '/poll.latte');
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class SomeController extends AbstractController
{
     public function some(): Response
     {
         return $this->render(__DIR__ . '/poll.latte', ['param' => 'some value']);
     }
}
CODE_SAMPLE
),

        ]);
    }

    /**
     * @return string[]
     */
    public function getNodeTypes(): array
    {
        return [Class_::class];
    }

    /**
     * @param Class_ $node
     */
    public function refactor(Node $node): ?Node
    {
        if ($this->shouldSkipClass($node)) {
            return null;
        }
        $shortClassName = $this->nodeNameResolver->getShortName($node);
        $shortClassName = $this->removeSuffix($shortClassName, 'Control');
        $shortClassName .= 'Controller';
        $node->name = new Identifier($shortClassName);
        $node->extends = new FullyQualified(AbstractController::class);
        $classMethod = $node->getMethod('render');
        if ($classMethod !== null) {
            $this->processRenderMethod($classMethod);
        }
        return $node;
    }

    private function shouldSkipClass(Class_ $class): bool
    {
        if ($this->classNodeAnalyzer->isAnonymousClass($class)) {
            return true;
        }
        // skip presenter
        if ($this->isName($class, '*Presenter')) {
            return true;
        }
        return ! $this->isObjectType($class, 'Nette\Application\UI\Control');
    }

    private function removeSuffix(string $content, string $suffix): string
    {
        if (! Strings::endsWith($content, $suffix)) {
            return $content;
        }
        return Strings::substring($content, 0, -Strings::length($suffix));
    }

    private function processRenderMethod(ClassMethod $classMethod): void
    {
        $this->processGetPresenterGetSessionMethodCall($classMethod);
        $classMethod->name = new Identifier('action');
        $magicTemplatePropertyCalls = $this->templatePropertyAssignCollector->collectMagicTemplatePropertyCalls($classMethod);
        $methodCall = $this->actionRenderFactory->createThisRenderMethodCall($magicTemplatePropertyCalls);
        // add return in the end
        $return = new Return_($methodCall);
        $classMethod->stmts[] = $return;
        if ($this->isAtLeastPhpVersion(PhpVersionFeature::SCALAR_TYPES)) {
            $classMethod->returnType = new FullyQualified(Response::class);
        }
        $this->removeNodes($magicTemplatePropertyCalls->getNodesToRemove());
    }

    private function processGetPresenterGetSessionMethodCall(ClassMethod $classMethod): void
    {
        $this->traverseNodesWithCallable((array) $classMethod->getStmts(), function (Node $node): ?MethodCall {
            if (! $node instanceof MethodCall) {
                return null;
            }
            if (! $this->isName($node->name, 'getSession')) {
                return null;
            }
            if (! $node->var instanceof MethodCall) {
                return null;
            }
            if (! $this->isName($node->var->name, 'getPresenter')) {
                return null;
            }
            $node->var = new PropertyFetch(new Variable('this'), 'session');
            $classLike = $node->getAttribute(AttributeKey::CLASS_NODE);
            if (! $classLike instanceof Class_) {
                throw new ShouldNotHappenException();
            }
            $this->addConstructorDependencyToClass($classLike, new FullyQualifiedObjectType('Nette\Http\Session'), 'session');
            return $node;
        });
    }
}
