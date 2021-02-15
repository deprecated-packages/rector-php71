<?php

namespace Symplify\PHPStanRules\CognitiveComplexity\Tests\Rules\FunctionLikeCognitiveComplexityRule\Fixture;

final class ClassMethodOverComplicated
{
    public function someMethod($var)
    {
        try {
            if (true) { // +1
                for ($i = 0; $i < 10; $i++) { // +2 (nesting=1)
                    while (true) { // +3 (nesting=2)
                    }
                }
            }
        } catch (\Exception | \Exception $exception) { // +1
            if (true) { // +2 (nesting=1)
            }
        }
    }

    // Cognitive Complexity 9
}
