<?php

namespace Strukt\Rules\Spec;

/**
 * Specification for the `filled` rule.
 *
 * @see \Strukt\Rules\Rule
 */
final class FilledRule
{
    public const SPEC = ['filled' => ['min' => 0, 'max' => 0, 'comma' => false, 'message' => 'must have a value.', 'bails' => true]];
}