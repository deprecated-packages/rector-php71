<?php

declare(strict_types=1);

namespace Doctrine\Inflector;

use Doctrine\Inflector\Rules\Ruleset;

interface LanguageInflectorFactory
{
    /**
     * Applies custom rules for singularisation
     *
     * @param bool $reset If true, will unset default inflections for all new rules
     *
     * @return mixed
     */
    public function withSingularRules(?Ruleset $singularRules, bool $reset = false);

    /**
     * Applies custom rules for pluralisation
     *
     * @param bool $reset If true, will unset default inflections for all new rules
     *
     * @return mixed
     */
    public function withPluralRules(?Ruleset $pluralRules, bool $reset = false);

    /**
     * Builds the inflector instance with all applicable rules
     */
    public function build() : Inflector;
}
