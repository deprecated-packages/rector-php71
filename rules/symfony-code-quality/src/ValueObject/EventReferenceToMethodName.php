<?php

declare(strict_types=1);

namespace Rector\SymfonyCodeQuality\ValueObject;

use PhpParser\Node\Expr\ClassConstFetch;

final class EventReferenceToMethodName
{
    /**
     * @var ClassConstFetch
     */
    private $classConstFetch;

    /**
     * @var string
     */
    private $methodName;

    public function __construct(ClassConstFetch $classConstFetch, string $methodName)
    {
        $this->classConstFetch = $classConstFetch;
        $this->methodName = $methodName;
    }

    public function getClassConstFetch(): ClassConstFetch
    {
        return $this->classConstFetch;
    }

    public function getMethodName(): string
    {
        return $this->methodName;
    }
}
