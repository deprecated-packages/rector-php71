<?php

declare(strict_types=1);

namespace Symplify\CodingStandard\TokenRunner\DocBlock\MalformWorker;

use Nette\Utils\Strings;
use PhpCsFixer\Tokenizer\Tokens;
use Symplify\CodingStandard\TokenRunner\Contract\DocBlock\MalformWorkerInterface;

final class ParamNameReferenceMalformWorker implements MalformWorkerInterface
{
    /**
     * @var string
     * @see https://regex101.com/r/B4rWNk/3
     */
    private const PARAM_NAME_REGEX = '#(?<param>@param(.*?))&(?<paramName>\$\w+)#';

    public function work(string $docContent, Tokens $tokens, int $position): string
    {
        return Strings::replace($docContent, self::PARAM_NAME_REGEX, function ($match): string {
            return $match['param'] . $match['paramName'];
        });
    }
}
