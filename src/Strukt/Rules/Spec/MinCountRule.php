<?php

namespace Strukt\Rules\Spec;

/**
 * Specification for the `min_count` rule.
 *
 * @see \Strukt\Rules\Rule
 */
final class MinCountRule
{
    public const SPEC = ['min_count' => ['min' => 1, 'max' => 1, 'comma' => false, 'message' => 'must have at least :0 items.', 'bails' => false]];
}