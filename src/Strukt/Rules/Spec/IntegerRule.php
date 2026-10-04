<?php

namespace Strukt\Rules\Spec;

/**
 * Specification for the `integer` rule.
 *
 * @see \Strukt\Rules\Rule
 */
final class IntegerRule
{
    public const SPEC = ['integer' => ['min' => 0, 'max' => 0, 'comma' => false, 'message' => 'must be an integer.', 'bails' => true]];
}