<?php

namespace Strukt\Rules\Spec;

/**
 * Specification for the `array` rule.
 *
 * @see \Strukt\Rules\Rule
 */
final class ArrayRule
{
    public const SPEC = ['array' => ['min' => 0, 'max' => 0, 'comma' => false, 'message' => 'must be an array.', 'bails' => true]];
}