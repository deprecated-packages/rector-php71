<?php

declare(strict_types=1);

use PhpParser\Node\Expr\New_;
use PhpParser\Node\Name;

$class = new Name('SomeClass');

return new New_($class);
