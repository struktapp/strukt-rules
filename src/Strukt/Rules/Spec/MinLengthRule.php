<?php

namespace Strukt\Rules\Spec;

/**
 * Specification for the `min_length` rule.
 *
 * @see \Strukt\Rules\Rule
 */
final class MinLengthRule
{
    public const SPEC = ['min_length' => ['min' => 1, 'max' => 1, 'comma' => false, 'message' => 'must be at least :0 characters.', 'bails' => false]];
}