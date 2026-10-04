<?php

namespace Strukt\Rules\Spec;

/**
 * Specification for the `same` rule.
 *
 * @see \Strukt\Rules\Rule
 */
final class SameRule
{
    public const SPEC = ['same' => ['min' => 1, 'max' => 1, 'comma' => false, 'message' => 'must match :0.', 'bails' => false]];
}