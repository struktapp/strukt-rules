<?php

namespace Strukt\Rules\Spec;

/**
 * Specification for the `max_length` rule.
 *
 * @see \Strukt\Rules\Rule
 */
final class MaxLengthRule
{
    public const SPEC = ['max_length' => ['min' => 1, 'max' => 1, 'comma' => false, 'message' => 'must be at most :0 characters.', 'bails' => false]];
}