<?php

declare(strict_types=1);

namespace Rector\NetteToSymfony\Rector\Class_;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Param;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Expression;
use Rector\Core\Rector\AbstractRector;
use Rector\NetteToSymfony\Collector\OnFormVariableMethodCallsCollector;
use Rector\NetteToSymfony\NodeFactory\SymfonyControllerFactory;
use Rector\NodeTypeResolver\Node\AttributeKey;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ExtraFileCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
use Symplify\SmartFileSystem\SmartFileInfo;

/**
 * @see https://symfony.com/doc/current/forms.html#creating-form-classes
 *
 * @see \Rector\NetteToSymfony\Tests\Rector\Class_\FormControlToControllerAndFormTypeRector\FormControlToControllerAndFormTypeRectorTest
 */
final class FormControlToControllerAndFormTypeRector extends AbstractRector
{
    /**
     * @var OnFormVariableMethodCallsCollector
     */
    private $onFormVariableMethodCallsCollector;

    /**
     * @var SymfonyControllerFactory
     */
    private $symfonyControllerFactory;

    public function __construct(OnFormVariableMethodCallsCollector $onFormVariableMethodCallsCollector, SymfonyControllerFactory $symfonyControllerFactory)
    {
        $this->onFormVariableMethodCallsCollector = $onFormVariableMethodCallsCollector;
        $this->symfonyControllerFactory = $symfonyControllerFactory;
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Change Form that extends Control to Controller and decoupled FormType', [
            new ExtraFileCodeSample(<<<'CODE_SAMPLE'
use Nette\Application\UI\Form;
use Nette\Application\UI\Control;

class SomeForm extends Control
{
    public function createComponentForm()
    {
        $form = new Form();
        $form->addText('name', 'Your name');

        $form->onSuccess[] = [$this, 'processForm'];
    }

    public function processForm(Form $form)
    {
        // process me
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
class SomeFormController extends \Symfony\Bundle\FrameworkBundle\Controller\AbstractController
{
    /**
     * @Route(...)
     */
    public function actionSomeForm(\Symfony\Component\HttpFoundation\Request $request): \Symfony\Component\HttpFoundation\Response
    {
        $form = $this->createForm(SomeFormType::class);
        $form->handleRequest($request);

        if ($form->isSuccess() && $form->isValid()) {
            // process me
        }
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
<?php

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class SomeFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $formBuilder, array $options)
    {
        $formBuilder->add('name', TextType::class, [
            'label' => 'Your name'
        ]);
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
        if (! $this->isObjectType($node, 'Nette\Application\UI\Control')) {
            return null;
        }
        foreach ($node->getMethods() as $classMethod) {
            if (! $this->isName($classMethod->name, 'createComponent*')) {
                continue;
            }

            $formTypeClass = $this->collectFormMethodCallsAndCreateFormTypeClass($classMethod);
            if ($formTypeClass === null) {
                continue;
            }

            $symfonyControllerNamespace = $this->symfonyControllerFactory->createNamespace($node, $formTypeClass);
            if ($symfonyControllerNamespace === null) {
                continue;
            }

            /** @var SmartFileInfo $smartFileInfo */
            $smartFileInfo = $node->getAttribute(AttributeKey::FILE_INFO);
            $filePath = dirname($smartFileInfo->getRealPath()) . DIRECTORY_SEPARATOR . 'SomeFormController.php';

            $this->printToFile([$symfonyControllerNamespace], $filePath);

            return $formTypeClass;
        }
        return $node;
    }

    private function collectFormMethodCallsAndCreateFormTypeClass(ClassMethod $classMethod): ?Class_
    {
        $onFormVariableMethodCalls = $this->onFormVariableMethodCallsCollector->collectFromClassMethod($classMethod);
        if ($onFormVariableMethodCalls === []) {
            return null;
        }
        $formBuilderVariable = new Variable('formBuilder');
        // public function buildForm(\Symfony\Component\Form\FormBuilderInterface $formBuilder, array $options)
        $buildFormClassMethod = $this->createBuildFormClassMethod($formBuilderVariable);
        $symfonyMethodCalls = [];
        // create symfony form from nette form method calls
        foreach ($onFormVariableMethodCalls as $onFormVariableMethodCall) {
            if ($this->isName($onFormVariableMethodCall->name, 'addText')) {
                // text input
                $inputName = $onFormVariableMethodCall->args[0];
                $formTypeClassConstant = $this->createClassConstantReference(TextType::class);

                $args = $this->createAddTextArgs($inputName, $formTypeClassConstant, $onFormVariableMethodCall);
                $methodCall = new MethodCall($formBuilderVariable, 'add', $args);

                $symfonyMethodCalls[] = new Expression($methodCall);
            }
        }
        $buildFormClassMethod->stmts = $symfonyMethodCalls;
        return $this->createFormTypeClassFromBuildFormClassMethod($buildFormClassMethod);
    }

    private function createBuildFormClassMethod(Variable $formBuilderVariable): ClassMethod
    {
        $buildFormClassMethod = $this->nodeFactory->createPublicMethod('buildForm');
        $buildFormClassMethod->params[] = new Param($formBuilderVariable, null, new FullyQualified('Symfony\Component\Form\FormBuilderInterface'));
        $buildFormClassMethod->params[] = new Param(new Variable('options'), null, new Identifier('array'));
        return $buildFormClassMethod;
    }

    /**
     * @return Arg[]
     */
    private function createAddTextArgs(Arg $arg, ClassConstFetch $classConstFetch, MethodCall $onFormVariableMethodCall): array
    {
        $args = [$arg, new Arg($classConstFetch)];
        if (isset($onFormVariableMethodCall->args[1])) {
            $optionsArray = new Array_([
                new ArrayItem($onFormVariableMethodCall->args[1]->value, new String_('label')),
            ]);

            $args[] = new Arg($optionsArray);
        }
        return $args;
    }

    private function createFormTypeClassFromBuildFormClassMethod(ClassMethod $buildFormClassMethod): Class_
    {
        $formTypeClass = new Class_('SomeFormType');
        $formTypeClass->extends = new FullyQualified('Symfony\Component\Form\AbstractType');
        $formTypeClass->stmts[] = $buildFormClassMethod;
        return $formTypeClass;
    }
}
