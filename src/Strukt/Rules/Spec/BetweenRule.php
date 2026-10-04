<?php

namespace Strukt\Rules\Spec;

/**
 * Specification for the `between` rule.
 *
 * @see \Strukt\Rules\Rule
 */
final class BetweenRule
{
    public const SPEC = ['between' => ['min' => 2, 'max' => 2, 'comma' => true, 'message' => 'must be between :0 and :1.', 'bails' => false]];
}