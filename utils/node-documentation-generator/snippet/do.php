<?php

declare(strict_types=1);

use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\Do_;

$variable = new Variable('variableName');

return new Do_($variable);
