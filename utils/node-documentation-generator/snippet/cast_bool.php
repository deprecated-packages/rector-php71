<?php

declare(strict_types=1);

use PhpParser\Node\Expr\Cast\Bool_;
use PhpParser\Node\Expr\Variable;

$expr = new Variable('variableName');

return new Bool_($expr);
