<?php

namespace Strukt\Rules\Spec;

/**
 * Specification for the `min` rule.
 *
 * @see \Strukt\Rules\Rule
 */
final class MinRule
{
    public const SPEC = ['min' => ['min' => 1, 'max' => 1, 'comma' => false, 'message' => 'must be at least :0.', 'bails' => false]];
}