<?php

namespace Strukt\Rules\Spec;

/**
 * Specification for the `boolean` rule.
 *
 * @see \Strukt\Rules\Rule
 */
final class BooleanRule
{
    public const SPEC = ['boolean' => ['min' => 0, 'max' => 0, 'comma' => false, 'message' => 'must be true or false.', 'bails' => true]];
}