<?php

declare(strict_types=1);

use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\If_;

$cond = new ConstFetch(new Name('true'));

return new If_($cond);
