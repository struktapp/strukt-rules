<?php

namespace Strukt\Rules\Spec;

/**
 * Specification for the `max_count` rule.
 *
 * @see \Strukt\Rules\Rule
 */
final class MaxCountRule
{
    public const SPEC = ['max_count' => ['min' => 1, 'max' => 1, 'comma' => false, 'message' => 'must have at most :0 items.', 'bails' => false]];
}