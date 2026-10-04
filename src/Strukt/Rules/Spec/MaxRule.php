<?php

namespace Strukt\Rules\Spec;

/**
 * Specification for the `max` rule.
 *
 * @see \Strukt\Rules\Rule
 */
final class MaxRule
{
    public const SPEC = ['max' => ['min' => 1, 'max' => 1, 'comma' => false, 'message' => 'must be at most :0.', 'bails' => false]];
}