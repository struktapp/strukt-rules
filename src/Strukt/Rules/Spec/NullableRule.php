<?php

namespace Strukt\Rules\Spec;

/**
 * Specification for the `nullable` rule.
 *
 * @see \Strukt\Rules\Rule
 */
final class NullableRule
{
    public const SPEC = ['nullable' => ['min' => 0, 'max' => 0, 'comma' => false, 'message' => 'may not be null.', 'bails' => false]];
}