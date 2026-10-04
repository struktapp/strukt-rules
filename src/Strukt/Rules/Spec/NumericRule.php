<?php

namespace Strukt\Rules\Spec;

/**
 * Specification for the `numeric` rule.
 *
 * @see \Strukt\Rules\Rule
 */
final class NumericRule
{
    public const SPEC = ['numeric' => ['min' => 0, 'max' => 0, 'comma' => false, 'message' => 'must be a number.', 'bails' => true]];
}