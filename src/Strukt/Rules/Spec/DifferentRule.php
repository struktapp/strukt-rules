<?php

namespace Strukt\Rules\Spec;

/**
 * Specification for the `different` rule.
 *
 * @see \Strukt\Rules\Rule
 */
final class DifferentRule
{
    public const SPEC = ['different' => ['min' => 1, 'max' => 1, 'comma' => false, 'message' => 'must not match :0.', 'bails' => false]];
}